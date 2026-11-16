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
        private readonly NotificacionService $notificaciones,
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
            if (in_array($comando, ['4', 'operador', 'asesor', 'humano', 'cambiar numero'], true)) {
                $conversacion->escalar();

                return 'Te transferimos con un operador para verificar tu identidad y ayudarte con tu cuenta.';
            }

            $conversacion->update(['paso_actual' => 'esperando_identificacion']);

            return implode(' ', [
                '¡Hola! No encontramos un cliente asociado a este número.',
                'Si ya sos cliente, escribí OPERADOR para verificar tu identidad o escribinos desde el número que tenés registrado.',
                'Si todavía no sos cliente, escribí REGISTRARME para iniciar el alta.',
            ]);
        }

        if ($mensaje->tipo === TipoMensaje::Texto) {
            $this->notificaciones->retomarReenvioOcr($cliente, $conversacion, $mensaje);
        }

        if (in_array($comando, ['pagar', 'como pagar', 'datos de pago', 'alias', 'mercado pago'], true)) {
            $conversacion->update(['paso_actual' => null, 'intencion_actual' => null, 'intentos_interpretacion' => 0]);

            return $this->responderDatosPago($cliente);
        }

        if (in_array($comando, ['menu', 'hola', 'buenas', 'buen dia', 'buenos dias', 'buenas tardes', 'buenas noches'], true)) {
            $conversacion->update([
                'paso_actual' => null,
                'intencion_actual' => null,
                'intentos_interpretacion' => 0,
            ]);

            return $comando !== 'menu' && $this->iaService->proveedorActivo($conversacion->numero_whatsapp) !== null
                ? "¡Hola {$cliente->nombre_razon_social}! Contame qué necesitás y te ayudo con tu cuenta, un pago o un problema con la conexión."
                : $this->mensajeBienvenida($cliente);
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

        $consultaVencimiento = preg_match(
            '/^(?:y )?(?:cuando|que dia|en que fecha) (?:(?:me |se )?(?:vence|vencia|vencio)|(?:vence|vencia|vencio) (?:mi cuota|la cuota|el pago))$/D',
            $comando,
        ) === 1;
        $resultado = $consultaVencimiento
            ? ['intencion' => IntencionConversacion::ConsultaCuenta, 'pregunta' => null]
            : $this->iaService->interpretarIntencion(
                (string) $mensaje->contenido,
                $this->iaService->proveedorActivo($conversacion->numero_whatsapp) === null ? '' : $this->construirContexto($conversacion, $cliente),
                $conversacion->numero_whatsapp,
            );
        $intencion = $resultado['intencion'];

        if ($intencion === IntencionConversacion::Desconocida) {
            return $this->manejarIntencionDesconocida($conversacion, $resultado['pregunta']);
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
            IntencionConversacion::ReclamoSoporte => 'Contame un poco más sobre lo que pasa con tu conexión y registro el reclamo.',
            IntencionConversacion::EnvioComprobante => 'Podés adjuntar una foto clara o el PDF de tu comprobante por acá.',
            IntencionConversacion::DatosPago => $this->responderDatosPago($cliente),
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
        $total = number_format((float) $resumen['total_pendiente'], 2, ',', '.');
        $respuesta = match ($resumen['estado']) {
            'con_deuda' => "Hay cuotas vencidas en tu cuenta. Total pendiente: \${$total}.",
            'al_dia_con_cuotas' => "No tenés cuotas vencidas. Total pendiente: \${$total}.",
            default => 'Estás al día, sin cuotas pendientes.',
        };

        if ($resumen['vencimiento_impago_mas_antiguo'] !== null) {
            $fechaVencida = CarbonImmutable::parse($resumen['vencimiento_impago_mas_antiguo'])->format('d/m/Y');
            $respuesta .= $resumen['cantidad_vencidas'] === 1
                ? " La cuota venció el {$fechaVencida}."
                : " La cuota impaga más antigua venció el {$fechaVencida}.";
        }
        if ($resumen['proximo_vencimiento'] !== null) {
            $fecha = CarbonImmutable::parse($resumen['proximo_vencimiento'])->format('d/m/Y');
            $respuesta .= " Tu próxima cuota vence el {$fecha}.";
        }
        $conversacion->cerrar();

        return $respuesta;
    }

    private function responderDatosPago(Cliente $cliente): string
    {
        return $this->servicioCuentaCorriente->instruccionesTransferencia($cliente)
            ?? 'La cuenta receptora todavía no está disponible. Si querés, puedo derivarte con un operador antes de que hagas la transferencia.';
    }

    private function manejarIntencionDesconocida(Conversacion $conversacion, ?string $pregunta): string
    {
        $conversacion->registrarIntentoFallido();

        if ($conversacion->intentos_interpretacion >= 2) {
            $conversacion->escalar();

            return 'No pude interpretar tu consulta después de dos intentos. Te transferimos con un operador. Si preferís volver al bot mientras esperás, escribí MENU.';
        }

        $conversacion->update(['paso_actual' => 'esperando_reformulacion']);

        return $pregunta ?? '¿Me lo contás con otras palabras? Puedo ayudarte con tu cuenta, un comprobante o un problema de conexión.';
    }

    private function pasoPara(IntencionConversacion $intencion): ?string
    {
        return match ($intencion) {
            IntencionConversacion::ConsultaCuenta => 'consultando_cuenta',
            IntencionConversacion::ReclamoSoporte => 'recopilando_reclamo',
            IntencionConversacion::EnvioComprobante => 'esperando_comprobante',
            IntencionConversacion::DatosPago => null,
            IntencionConversacion::AtencionHumana => 'esperando_operador',
            IntencionConversacion::Desconocida => null,
        };
    }

    private function construirContexto(Conversacion $conversacion, Cliente $cliente): string
    {
        // Solo se comparte la intención, sin reenviar el historial ni datos de cuenta.
        if ($conversacion->intencion_actual !== null) {
            return $conversacion->intencion_actual->value;
        }

        $anterior = Conversacion::query()
            ->where('id_cliente', $cliente->id_cliente)
            ->where('numero_whatsapp', $conversacion->numero_whatsapp)
            ->where('modo_atencion', ModoAtencion::Bot->value)
            ->where('fecha_hora_cierre', '>=', now()->subMinutes(10))
            ->latest('fecha_hora_cierre')
            ->latest('id_conversacion')
            ->first();

        return $anterior?->intencion_actual?->value ?? '';
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
