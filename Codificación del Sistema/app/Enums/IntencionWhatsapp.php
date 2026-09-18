<?php

namespace App\Enums;

/** Intenciones que el bot admite como resultado de la clasificación. */
enum IntencionWhatsapp: string
{
    case ConsultarEstadoCuenta = 'consultar_estado_cuenta';
    case InformarPago = 'informar_pago';
    case RegistrarReclamo = 'registrar_reclamo';
    case SolicitarAtencionHumana = 'solicitar_atencion_humana';
    case NoReconocida = 'no_reconocida';

    public function opcionMenu(): ?OpcionMenuWhatsapp
    {
        return match ($this) {
            self::ConsultarEstadoCuenta => OpcionMenuWhatsapp::ConsultarEstadoCuenta,
            self::InformarPago => OpcionMenuWhatsapp::InformarPago,
            self::RegistrarReclamo => OpcionMenuWhatsapp::RegistrarReclamo,
            self::SolicitarAtencionHumana => OpcionMenuWhatsapp::SolicitarAtencionHumana,
            self::NoReconocida => null,
        };
    }
}
