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
    ) {}

    /**
     * Atiende las opciones ya implementadas y deja las restantes disponibles
     * para incorporarlas progresivamente sin mezclar sus reglas de negocio.
     */
    public function responder(Conversacion $conversacion, Cliente $cliente, ?string $contenido): ?Mensaje
    {
        $textoNormalizado = Str::of((string) $contenido)->ascii()->lower()->squish()->toString();
        if ($textoNormalizado === 'menu') {
            return $this->atencionInicial->mostrarMenu($conversacion);
        }

        $opcion = OpcionMenuWhatsapp::desdeMensaje($contenido);

        return match ($opcion) {
            OpcionMenuWhatsapp::ConsultarEstadoCuenta => $this->consultaCuenta->responder($conversacion, $cliente),
            OpcionMenuWhatsapp::InformarPago => $this->registroComprobante->solicitarComprobante($conversacion),
            default => null,
        };
    }
}
