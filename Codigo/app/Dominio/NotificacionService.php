<?php

namespace App\Dominio;

use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Exceptions\LimiteConsumoWhatsappException;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Mensaje;
use App\Models\Pago;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Throwable;

class NotificacionService
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly ServicioConversaciones $conversaciones,
    ) {}

    public function notificarNovedad(Ticket $ticket, string $detalle = ''): array
    {
        $ticket->refresh();
        $cliente = $ticket->servicio->cliente;
        $contenido = sprintf('Villafañe Wifi (demostración académica). Hola %s, el ticket #%d se actualizó a: %s. Podés escribirnos para consultar su seguimiento.',
            $cliente->nombre_razon_social, $ticket->id_ticket, str_replace('_', ' ', $ticket->estado->value));

        return $this->enviar($cliente, 'ticket:estado:'.$ticket->id_ticket.':'.$ticket->estado->value.':'.$ticket->notas()->max('id_nota'),
            $contenido, '', [], 'tickets');
    }

    public function notificarPagoAprobado(Cliente $cliente, Pago $pago, ?Comprobante $comprobante = null): array
    {
        $contenido = sprintf('Villafañe Wifi (demostración académica). Hola %s, registramos tu pago de $%s del %s y actualizamos las cuotas correspondientes. No es necesario volver a pagar esas cuotas.',
            $cliente->nombre_razon_social, number_format((float) $pago->monto_total, 2, ',', '.'), $pago->fecha->format('d/m/Y'));

        return $this->enviar($cliente, 'comprobante:aprobado:'.($comprobante?->id_comprobante ?? 'pago-'.$pago->id_pago), $contenido,
            (string) config('services.comprobantes.plantilla_aprobado'),
            [$cliente->nombre_razon_social, '$'.number_format((float) $pago->monto_total, 2, ',', '.'), $pago->fecha->format('d/m/Y')]);
    }

    public function notificarPagoRechazado(Cliente $cliente, Comprobante $comprobante, string $motivo): array
    {
        $contenido = sprintf('Villafañe Wifi (demostración académica). Hola %s, el comprobante #%d fue rechazado. Motivo: %s. Podés escribirnos para revisar el caso o enviar un comprobante corregido.',
            $cliente->nombre_razon_social, $comprobante->id_comprobante, $motivo);

        return $this->enviar($cliente, 'comprobante:rechazado:'.$comprobante->id_comprobante, $contenido,
            (string) config('services.comprobantes.plantilla_rechazado'),
            [$cliente->nombre_razon_social, (string) $comprobante->id_comprobante, $motivo]);
    }

    /** @param list<string> $parametros */
    private function enviar(Cliente $cliente, string $clave, string $contenido, string $plantilla, array $parametros, string $modulo = 'comprobantes'): array
    {
        try {
            return $this->procesarEnvio($cliente, $clave, $contenido, $plantilla, $parametros, $modulo);
        } catch (Throwable) {
            return ['estado' => 'fallido', 'detalle' => 'El resultado quedó guardado; no se pudo procesar el aviso. Podés reintentarlo.'];
        }
    }

    /** @param list<string> $parametros */
    private function procesarEnvio(Cliente $cliente, string $clave, string $contenido, string $plantilla, array $parametros, string $modulo): array
    {
        $resultado = ['estado' => 'pendiente', 'detalle' => 'El aviso no se envió: las notificaciones de '.$modulo.' están desactivadas.'];
        if (! config('services.'.$modulo.'.notificar_resultado', false)) {
            return $resultado;
        }
        $telefono = preg_replace('/\D/', '', $cliente->telefono_whatsapp ?? '');
        if (blank($telefono)) {
            return ['estado' => 'pendiente', 'detalle' => 'El cliente no tiene un teléfono registrado.'];
        }
        $telefonos = [$telefono];
        if (str_starts_with($telefono, '549')) {
            $telefonos[] = '54'.substr($telefono, 3);
        } elseif (str_starts_with($telefono, '54')) {
            $telefonos[] = '549'.substr($telefono, 2);
        }
        $ultimoEntrante = Mensaje::query()->where('tipo_emisor', TipoEmisorMensaje::Cliente->value)
            ->whereHas('conversacion', fn ($q) => $q->where('id_cliente', $cliente->id_cliente)->whereIn('numero_whatsapp', $telefonos))
            ->where('fecha_hora', '<=', now())
            ->latest('fecha_hora')->first();
        if ($ultimoEntrante === null) {
            return ['estado' => 'pendiente', 'detalle' => 'Aviso pendiente: no hay un mensaje previo del cliente en este teléfono.'];
        }
        $fueraDeVentana = $ultimoEntrante->fecha_hora->lte(now()->subHours(24));
        if ($fueraDeVentana && (! config('services.'.$modulo.'.plantillas_fuera_ventana', false) || blank($plantilla))) {
            return ['estado' => 'pendiente', 'detalle' => 'Aviso pendiente: el cliente debe volver a escribir o se deben habilitar las plantillas de resultado aprobadas por Meta.'];
        }
        $lock = Cache::lock('notificacion:'.$clave, 90);
        if (! $lock->get()) {
            return ['estado' => 'pendiente', 'detalle' => 'El aviso ya está siendo procesado.'];
        }
        try {
            $mensaje = $this->conversaciones->reservarNotificacion($ultimoEntrante->conversacion, $clave, $contenido);
            if ($mensaje === null) {
                $estado = Mensaje::where('clave_idempotencia', $clave)->value('estado_envio');

                return ['estado' => $estado instanceof EstadoEnvioMensaje ? $estado->value : (string) $estado,
                    'detalle' => 'El aviso ya está registrado. Revisá su estado en el historial de la conversación.'];
            }
            try {
                $idExterno = $fueraDeVentana
                    ? $this->whatsapp->enviarPlantilla($telefono, $plantilla, (string) config('services.whatsapp.idioma_plantilla', 'es_AR'), $parametros)
                    : $this->whatsapp->enviarMensaje($telefono, $contenido);
                $mensaje->marcarEnviado($idExterno);

                return ['estado' => 'enviado', 'detalle' => 'Meta aceptó el aviso. Su entrega se puede consultar en la conversación.'];
            } catch (LimiteConsumoWhatsappException $error) {
                $mensaje->marcarFallido();

                return ['estado' => 'bloqueado', 'detalle' => $error->getMessage().' La decisión permanece guardada.'];
            } catch (Throwable) {
                $mensaje->marcarFallido();

                return ['estado' => 'fallido', 'detalle' => 'La actualización quedó guardada, pero no se pudo enviar el aviso.'];
            }
        } catch (Throwable) {
            return ['estado' => 'fallido', 'detalle' => 'El resultado quedó guardado; no se pudo registrar el aviso. Podés reintentarlo.'];
        } finally {
            $lock->release();
        }
    }
}
