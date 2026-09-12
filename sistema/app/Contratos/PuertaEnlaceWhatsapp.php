<?php

namespace App\Contratos;

/**
 * Define las operaciones externas necesarias para comunicarse con WhatsApp.
 *
 * El dominio depende de este contrato y no de la API concreta de Meta. Esto
 * permite reemplazar la integración real por una simulación durante pruebas.
 */
interface PuertaEnlaceWhatsapp
{
    /**
     * Envía un mensaje de texto y devuelve el identificador asignado por Meta.
     */
    public function enviarTexto(string $numeroDestino, string $contenido): string;
}
