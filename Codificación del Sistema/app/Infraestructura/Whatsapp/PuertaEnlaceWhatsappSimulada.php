<?php

namespace App\Infraestructura\Whatsapp;

use App\Contratos\PuertaEnlaceWhatsapp;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Registra respuestas locales sin transmitirlas a Meta. */
class PuertaEnlaceWhatsappSimulada implements PuertaEnlaceWhatsapp
{
    public function enviarTexto(string $numeroDestino, string $contenido): string
    {
        if (blank($numeroDestino) || blank($contenido)) {
            throw ValidationException::withMessages([
                'mensaje' => 'El destino y el contenido simulado son obligatorios.',
            ]);
        }

        return 'wamid.simulado.salida.'.Str::uuid();
    }
}
