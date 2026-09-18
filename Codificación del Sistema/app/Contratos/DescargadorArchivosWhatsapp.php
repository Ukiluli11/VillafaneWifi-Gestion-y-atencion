<?php

namespace App\Contratos;

/** Obtiene un archivo recibido por WhatsApp y devuelve su referencia local. */
interface DescargadorArchivosWhatsapp
{
    public function descargar(string $identificadorArchivo): string;
}
