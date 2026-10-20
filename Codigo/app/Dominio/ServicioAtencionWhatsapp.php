<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Enums\IntencionConversacion;
use App\Enums\ModoAtencion;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ServicioAtencionWhatsapp: Orquesta el bot conversacional inteligente para atencion al cliente.
 * Corresponde a los casos de uso: 'Atender conversacion por WhatsApp', 'Reconocer intencion mediante IA',
 * 'Consultar estado de cuenta corriente', 'Registrar reclamo tecnico' y 'Derivar tras segundo fallo de IA'.
 * Conecta con IAService, ServicioCuentaCorriente, ServicioTickets y ServicioRecepcionComprobantes.
 */
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
        $comando = $mensaje->tipo === TipoMensaje::Texto
            ? trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii((string) $mensaje->contenido))))
            : '';

        if ($conversacion->modo_atencion === ModoAtencion::Humano) {
            if ($comando !== 'menu' || ! $this->retomarBotEnEspera($conversacion)) {
                return null;
            }
        }

        $this->reiniciarIntentosTrasInactividad($conversacion, $mensaje);

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

        if (in_array($comando, ['pagar', 'como pagar', 'datos de pago', 'alias', 'mercado pago'], true)) {
            $conversacion->update(['paso_actual' => null, 'intencion_actual' => null, 'intentos_interpretacion' => 0]);

            return $this->servicioCuentaCorriente->instruccionesTransferencia($cliente)
                ?? 'Los datos de la cuenta receptora no están disponibles. Escribí MENU para consultar tu cuenta o hablar con un operador antes de transferir.';
        }

        if (in_array($comando, ['menu', 'hola', 'buenas', 'buen dia', 'buenos dias', 'buenas tardes', 'buenas noches'], true)) {
            $conversacion->update([
                'paso_actual' => null,
                'intencion_actual' => null,
                'intentos_interpretacion' => 0,
            ]);

            return $this->mensajeBienvenida($cliente);
        }

        if (in_array($comando, ['gracias', 'muchas gracias', 'ok gracias', 'dale gracias'], true)) {
            $conversacion->reiniciarInterpretacion();

            return '¡De nada! Cuando necesites otra consulta, escribí MENU para ver las opciones.';
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

    private function retomarBotEnEspera(Conversacion $conversacion): bool
    {
        return DB::transaction(function () use ($conversacion): bool {
            $actual = Conversacion::query()->lockForUpdate()->findOrFail($conversacion->id_conversacion);
            if ($actual->estado !== EstadoConversacion::Escalada || $actual->id_usuario_atencion !== null) {
                return false;
            }

            $actual->update([
                'estado' => EstadoConversacion::Abierta,
                'modo_atencion' => ModoAtencion::Bot,
                'paso_actual' => null,
                'intencion_actual' => null,
                'intentos_interpretacion' => 0,
            ]);
            $conversacion->refresh();

            return true;
        });
    }

    private function reiniciarIntentosTrasInactividad(Conversacion $conversacion, Mensaje $mensaje): void
    {
        if ($conversacion->intentos_interpretacion === 0) {
            return;
        }

        $anterior = $conversacion->mensajes()
            ->where('tipo_emisor', TipoEmisorMensaje::Cliente->value)
            ->where('id_mensaje', '<', $mensaje->id_mensaje)
            ->latest('id_mensaje')
            ->first();

        if ($anterior !== null && $anterior->fecha_hora->lte($mensaje->fecha_hora->copy()->subMinutes(30))) {
            $conversacion->reiniciarInterpretacion();
        }
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

            return 'No pude interpretar tu consulta después de dos intentos. Te transferimos con un operador. Si preferís volver al bot mientras esperás, escribí MENU.';
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
        $respuesta = "¡Hola {$cliente->nombre_razon_social}! Elegí una opción: 1. Consultar cuenta · 2. Registrar reclamo · 3. Enviar comprobante · 4. Hablar con un operador.";
        if ($this->servicioCuentaCorriente->instruccionesTransferencia($cliente) !== null) {
            $respuesta .= ' Para ver la cuenta de la empresa y las instrucciones de transferencia, escribí PAGAR.';
        }

        return $respuesta;
    }
}
