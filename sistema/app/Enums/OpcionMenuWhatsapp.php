<?php

namespace App\Enums;

/**
 * Opciones principales que el bot ofrece al comenzar una conversación.
 */
enum OpcionMenuWhatsapp: string
{
    case ConsultarEstadoCuenta = '1';
    case InformarPago = '2';
    case RegistrarReclamo = '3';
    case SolicitarAtencionHumana = '4';

    /**
     * Devuelve el texto visible asociado a cada opción.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::ConsultarEstadoCuenta => 'Consultar estado de cuenta',
            self::InformarPago => 'Informar un pago',
            self::RegistrarReclamo => 'Registrar un reclamo',
            self::SolicitarAtencionHumana => 'Solicitar atención humana',
        };
    }
}
