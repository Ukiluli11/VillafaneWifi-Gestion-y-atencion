<?php

namespace App\Enums;

enum OpcionMenuWhatsapp: string
{
    case ConsultarCuenta = '1';
    case RegistrarReclamo = '2';
    case EnviarComprobante = '3';
    case HablarOperador = '4';

    public function intencion(): IntencionConversacion
    {
        return match ($this) {
            self::ConsultarCuenta => IntencionConversacion::ConsultaCuenta,
            self::RegistrarReclamo => IntencionConversacion::ReclamoSoporte,
            self::EnviarComprobante => IntencionConversacion::EnvioComprobante,
            self::HablarOperador => IntencionConversacion::AtencionHumana,
        };
    }

    public static function desdeMensaje(string $mensaje): ?self
    {
        $normalizado = trim(mb_strtolower($mensaje));

        foreach (self::cases() as $opcion) {
            if ($normalizado === $opcion->value || str_starts_with($normalizado, $opcion->value.'.')) {
                return $opcion;
            }
        }

        return null;
    }
}
