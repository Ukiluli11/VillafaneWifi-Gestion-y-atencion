<?php

namespace App\Dominio;

use App\Enums\IntencionConversacion;
use App\Enums\ModoAtencion;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;

class ServicioAtencionWhatsapp
{
    public function __construct(
        private readonly IAService $iaService,
        private readonly ServicioCuentaCorriente $servicioCuentaCorriente,
        private readonly ServicioTickets $servicioTickets,
        private readonly ServicioRegistroConversacional $servicioRegistro,
        private readonly ServicioRecepcionComprobantes $servicioComprobantes,
    ) {}

    public function procesarMensaje(
        Conversacion $conversacion,
        Mensaje $mensaje,
        ?Cliente $cliente,
    ): ?string {
        if ($conversacion->modo_atencion === ModoAtencion::Humano) {
            return null;
        }

        $respuestaRegistro = $this->servicioRegistro->procesar($conversacion, $mensaje, $cliente);
        if ($respuestaRegistro !== null) {
            return $respuestaRegistro;
        }

        if ($cliente === null) {
            $conversacion->update(['paso_actual' => 'esperando_identificacion']);

            return implode(' ', [
                '¡Hola! No encontramos un cliente asociado a este número.',
                'Por favor, indicanos tu DNI para identificarte.',
                'Si todavía no sos cliente, escribí REGISTRARME para iniciar el alta.',
            ]);
        }

        $respuestaComprobante = $this->servicioComprobantes->procesar($conversacion, $mensaje, $cliente);
        if ($respuestaComprobante !== null) {
            return $respuestaComprobante;
        }

        if (in_array($conversacion->paso_actual, ['esperando_identificacion', 'identificacion_confirmada'], true)) {
            $conversacion->update(['paso_actual' => null]);
            $conversacion->reiniciarInterpretacion();

            return $this->mensajeBienvenida($cliente);
        }

        if (mb_strtolower(trim((string) $mensaje->contenido)) === 'menu') {
            $conversacion->update(['paso_actual' => null]);

            return $this->mensajeBienvenida($cliente);
        }

        if ($conversacion->paso_actual === 'recopilando_reclamo') {
            return $this->registrarReclamo($conversacion, $cliente, (string) $mensaje->contenido);
        }

        $resultado = $this->iaService->interpretarIntencion(
            (string) $mensaje->contenido,
            $this->construirContexto($conversacion),
        );
        $intencion = $resultado['intencion'];

        if ($intencion === IntencionConversacion::Desconocida) {
            return $this->manejarIntencionDesconocida($conversacion);
        }

        $conversacion->reiniciarInterpretacion();
        $conversacion->update([
            'intencion_actual' => $intencion,
            'paso_actual' => $this->pasoPara($intencion),
        ]);

        if ($intencion === IntencionConversacion::AtencionHumana) {
            $conversacion->escalar();

            return 'Te transferimos con un operador. Un asesor responderá a la brevedad.';
        }

        return match ($intencion) {
            IntencionConversacion::ConsultaCuenta => $this->responderEstadoCuenta($conversacion, $cliente),
            IntencionConversacion::ReclamoSoporte => 'Entendí que necesitás registrar un reclamo. Contame brevemente qué problema estás teniendo.',
            IntencionConversacion::EnvioComprobante => 'Entendí que querés enviar un comprobante. Podés adjuntar una imagen o un archivo PDF.',
            default => $this->mensajeBienvenida($cliente),
        };
    }

    private function registrarReclamo(Conversacion $conversacion, Cliente $cliente, string $descripcion): string
    {
        if (mb_strlen(trim($descripcion)) < 5) {
            return 'Necesito una descripción un poco más detallada para registrar el reclamo.';
        }

        $ticket = $this->servicioTickets->crearDesdeConversacion($conversacion, $cliente, $descripcion);
        if ($ticket === null) {
            $conversacion->escalar();

            return 'No encontré un servicio asociado para generar el ticket. Te transferimos con un operador.';
        }

        $conversacion->update(['paso_actual' => null]);
        $conversacion->cerrar();

        return "Tu reclamo fue registrado con el Ticket #{$ticket->id_ticket}. El área técnica lo revisará.";
    }

    private function responderEstadoCuenta(Conversacion $conversacion, Cliente $cliente): string
    {
        $resumen = $this->servicioCuentaCorriente->resumir($cliente);
        $estado = match ($resumen['estado']) {
            'con_deuda' => 'EN MORA',
            'al_dia_con_cuotas' => 'AL DÍA CON CUOTAS PENDIENTES',
            default => 'AL DÍA',
        };
        $total = number_format((float) $resumen['total_pendiente'], 2, ',', '.');
        $respuesta = "Tu cuenta está {$estado}. Total pendiente: \${$total}.";

        if ($resumen['proximo_vencimiento'] !== null) {
            $fecha = CarbonImmutable::parse($resumen['proximo_vencimiento'])->format('d/m/Y');
            $respuesta .= " Próximo vencimiento: {$fecha}.";
        }
        $conversacion->cerrar();

        return $respuesta;
    }

    private function manejarIntencionDesconocida(Conversacion $conversacion): string
    {
        $conversacion->registrarIntentoFallido();

        if ($conversacion->intentos_interpretacion >= 2) {
            $conversacion->escalar();

            return 'No pude interpretar tu consulta después de dos intentos. Te transferimos con un operador.';
        }

        $conversacion->update(['paso_actual' => 'esperando_reformulacion']);

        return 'No logré entender tu mensaje. ¿Podrías indicarme con otras palabras si querés consultar tu cuenta, registrar un reclamo o enviar un comprobante?';
    }

    private function pasoPara(IntencionConversacion $intencion): ?string
    {
        return match ($intencion) {
            IntencionConversacion::ConsultaCuenta => 'consultando_cuenta',
            IntencionConversacion::ReclamoSoporte => 'recopilando_reclamo',
            IntencionConversacion::EnvioComprobante => 'esperando_comprobante',
            IntencionConversacion::AtencionHumana => 'esperando_operador',
            IntencionConversacion::Desconocida => null,
        };
    }

    private function construirContexto(Conversacion $conversacion): string
    {
        return $conversacion->mensajes()
            ->latest('id_mensaje')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn (Mensaje $mensaje): string => sprintf(
                '%s: %s',
                $mensaje->tipo_emisor->value,
                mb_substr((string) $mensaje->contenido, 0, 300),
            ))
            ->implode("\n");
    }

    private function mensajeBienvenida(Cliente $cliente): string
    {
        return "¡Hola {$cliente->nombre_razon_social}! Elegí una opción: 1. Consultar cuenta · 2. Registrar reclamo · 3. Enviar comprobante · 4. Hablar con un operador.";
    }
}
