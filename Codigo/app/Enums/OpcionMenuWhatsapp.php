<?php

namespace App\Enums;

enum OpcionMenuWhatsapp: string
{
    case ConsultarCuenta = '1';
    case RegistrarReclamo = '2';
    case EnviarComprobante = '3';
    case HablarOperador = '4';
    case ConsultarDatosPago = '5';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ConsultarCuenta => 'Consultar saldo y vencimientos',
            self::RegistrarReclamo => 'Registrar un reclamo técnico',
            self::EnviarComprobante => 'Enviar un comprobante',
            self::HablarOperador => 'Hablar con un operador',
            self::ConsultarDatosPago => 'Ver cómo pagar',
        };
    }

    public function intencion(): IntencionConversacion
    {
        return match ($this) {
            self::ConsultarCuenta => IntencionConversacion::ConsultaCuenta,
            self::RegistrarReclamo => IntencionConversacion::ReclamoSoporte,
            self::EnviarComprobante => IntencionConversacion::EnvioComprobante,
            self::HablarOperador => IntencionConversacion::AtencionHumana,
            self::ConsultarDatosPago => IntencionConversacion::DatosPago,
        };
    }

    public static function desdeMensaje(string $mensaje): ?self
    {
        if (! preg_match('/\A#?\s*([1-9][0-9]*)(?:[.)](?:\s+\p{L}[^\r\n]*)?)?\z/u', trim($mensaje), $partes)) {
            return null;
        }

        return self::tryFrom($partes[1]);
    }
}
