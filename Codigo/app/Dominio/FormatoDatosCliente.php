<?php

namespace App\Dominio;

final class FormatoDatosCliente
{
    public const SIN_NUMERO_NEGATIVO = '/\A(?!\s*[-−]\s*[0-9])/u';

    public static function documento(mixed $valor): ?string
    {
        if (! is_string($valor) && ! is_int($valor)) {
            return null;
        }

        $valor = trim((string) $valor);

        return preg_match('/\A[0-9]+(?:[. \-][0-9]+)*\z/', $valor) === 1
            ? str_replace(['.', ' ', '-'], '', $valor)
            : null;
    }

    public static function telefono(mixed $valor): ?string
    {
        if (! is_string($valor) && ! is_int($valor)) {
            return null;
        }

        $valor = trim((string) $valor);

        return preg_match('/\A\+?(?:[0-9]++|\([0-9]+\))(?:(?:[ \-]+)?(?:[0-9]++|\([0-9]+\)))*\z/', $valor) === 1
            ? str_replace(['+', '(', ')', ' ', '-'], '', $valor)
            : null;
    }
}
