<?php

namespace App\Infraestructura\Whatsapp;

use App\Contratos\DescargadorArchivosWhatsapp;

/** Conserva la referencia externa mientras se trabaja sin la API real. */
class DescargadorArchivosWhatsappSimulado implements DescargadorArchivosWhatsapp
{
    public function descargar(string $identificadorArchivo): string
    {
        return 'meta-media:'.$identificadorArchivo;
    }
}
