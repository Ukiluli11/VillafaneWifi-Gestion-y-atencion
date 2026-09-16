<?php

namespace App\Dominio;

use App\Enums\EstadoFlujoWhatsapp;
use App\Enums\EstadoValidacionComprobante;
use App\Enums\TipoMensaje;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\Mensaje;

/**
 * Gestiona el tramo conversacional en el que el cliente informa un pago.
 */
class ServicioRegistroComprobanteWhatsapp
{
    public function __construct(
        private readonly ServicioEnvioWhatsapp $envioWhatsapp,
    ) {}

    /**
     * Solicita al cliente la imagen o el PDF que respalda la transferencia.
     */
    public function solicitarComprobante(Conversacion $conversacion): Mensaje
    {
        $conversacion->update([
            'estado_flujo' => EstadoFlujoWhatsapp::EsperandoComprobante,
        ]);

        return $this->envioWhatsapp->enviarTextoDelBot(
            $conversacion,
            "Para informar el pago, enviá ahora una foto o un archivo PDF del comprobante.\n"
            .'Lo registraremos como pendiente hasta que un empleado verifique sus datos.',
        );
    }

    /**
     * Registra el archivo recibido o recuerda el formato admitido.
     */
    public function recibirComprobante(Conversacion $conversacion, Mensaje $mensaje): Mensaje
    {
        if (! in_array($mensaje->tipo, [TipoMensaje::Imagen, TipoMensaje::Documento], true)) {
            return $this->envioWhatsapp->enviarTextoDelBot(
                $conversacion,
                'Necesito que envíes una imagen o un archivo PDF del comprobante para continuar.',
            );
        }

        Comprobante::query()->firstOrCreate(
            ['id_mensaje' => $mensaje->id_mensaje],
            [
                'fecha_recepcion' => $mensaje->fecha_hora,
                'estado_validacion' => EstadoValidacionComprobante::Pendiente,
            ],
        );

        $conversacion->update(['estado_flujo' => EstadoFlujoWhatsapp::Menu]);

        return $this->envioWhatsapp->enviarTextoDelBot(
            $conversacion,
            "Recibimos tu comprobante correctamente. Quedó pendiente de validación.\n"
            .'Te avisaremos por este medio cuando sea aprobado o si necesitamos que lo reenvíes.',
        );
    }
}
