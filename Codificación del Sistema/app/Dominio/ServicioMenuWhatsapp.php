<?php

namespace App\Dominio;

use App\Enums\OpcionMenuWhatsapp;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Support\Str;

/**
 * Interpreta las opciones elegidas por el cliente y deriva cada caso de uso.
 */
class ServicioMenuWhatsapp
{
    public function __construct(
        private readonly ServicioConsultaCuentaWhatsapp $consultaCuenta,
        private readonly ServicioAtencionInicialWhatsapp $atencionInicial,
        private readonly ServicioRegistroComprobanteWhatsapp $registroComprobante,
        private readonly ServicioRegistroReclamoWhatsapp $registroReclamo,
        private readonly ServicioReconocimientoIntencionWhatsapp $reconocimientoIntencion,
        private readonly ServicioEnvioWhatsapp $envioWhatsapp,
        private readonly ServicioConversaciones $conversaciones,
    ) {}

    /**
     * Atiende las opciones ya implementadas y deja las restantes disponibles
     * para incorporarlas progresivamente sin mezclar sus reglas de negocio.
     */
    public function responder(Conversacion $conversacion, Cliente $cliente, ?string $contenido): ?Mensaje
    {
        $textoNormalizado = Str::of((string) $contenido)->ascii()->lower()->squish()->toString();
        if ($textoNormalizado === 'menu') {
            $this->conversaciones->reiniciarIntentosIntencion($conversacion);

            return $this->atencionInicial->mostrarMenu($conversacion);
        }

        $resultado = $this->reconocimientoIntencion->reconocer($contenido);
        $opcion = $resultado->intencion->opcionMenu();
        if ($opcion === null) {
            return $this->responderIntencionNoReconocida($conversacion);
        }

        $this->conversaciones->reiniciarIntentosIntencion($conversacion);

        return match ($opcion) {
            OpcionMenuWhatsapp::ConsultarEstadoCuenta => $this->consultaCuenta->responder($conversacion, $cliente),
            OpcionMenuWhatsapp::InformarPago => $this->registroComprobante->solicitarComprobante($conversacion),
            OpcionMenuWhatsapp::RegistrarReclamo => $this->registroReclamo->solicitarDescripcion($conversacion, $cliente),
            OpcionMenuWhatsapp::SolicitarAtencionHumana => $this->derivarAAtencionHumana($conversacion),
        };
    }

    /** Solicita una reformulación y deriva en el segundo intento fallido. */
    private function responderIntencionNoReconocida(Conversacion $conversacion): Mensaje
    {
        $intentos = $this->conversaciones->registrarIntentoIntencionNoReconocida($conversacion);

        if ($intentos === 1) {
            return $this->envioWhatsapp->enviarTextoDelBot(
                $conversacion,
                'No logré identificar qué necesitás. Reformulá tu consulta o respondé con una opción del 1 al 4.',
            );
        }

        return $this->derivarAAtencionHumana(
            $conversacion,
            'No pude identificar tu consulta después de dos intentos. '
                .'La derivé a atención humana y el primer empleado disponible continuará por este chat.',
        );
    }

    /** Informa la derivación antes de detener las respuestas automáticas. */
    private function derivarAAtencionHumana(
        Conversacion $conversacion,
        string $mensaje = 'Derivamos tu conversación a atención humana. '
            .'El primer empleado disponible continuará por este chat.',
    ): Mensaje {
        $respuesta = $this->envioWhatsapp->enviarTextoDelBot($conversacion, $mensaje);
        $this->conversaciones->derivarAColaAtencion($conversacion->refresh());

        return $respuesta;
    }
}
