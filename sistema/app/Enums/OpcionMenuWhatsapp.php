<?php

namespace App\Enums;

use Illuminate\Support\Str;

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

    /**
     * Interpreta el número del menú o expresiones sencillas mientras se
     * incorpora el motor de lenguaje natural previsto para el bot.
     */
    public static function desdeMensaje(?string $mensaje): ?self
    {
        $texto = Str::of((string) $mensaje)->ascii()->lower()->squish()->toString();

        if ($texto === '') {
            return null;
        }

        return match (true) {
            $texto === '1', str_contains($texto, 'estado de cuenta'),
            str_contains($texto, 'deuda'), str_contains($texto, 'cuotas') => self::ConsultarEstadoCuenta,
            $texto === '2', str_contains($texto, 'informar pago') => self::InformarPago,
            $texto === '3', str_contains($texto, 'reclamo') => self::RegistrarReclamo,
            $texto === '4', str_contains($texto, 'atencion humana'),
            str_contains($texto, 'hablar con una persona') => self::SolicitarAtencionHumana,
            default => null,
        };
    }
}
