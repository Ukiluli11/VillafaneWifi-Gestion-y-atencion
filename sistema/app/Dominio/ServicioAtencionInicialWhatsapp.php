<?php

namespace App\Dominio;

use App\Contratos\PuertaEnlaceWhatsapp;
use App\Enums\OpcionMenuWhatsapp;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;

/**
 * Construye las respuestas utilizadas en el primer contacto por WhatsApp.
 */
class ServicioAtencionInicialWhatsapp
{
    public function __construct(
        private readonly ServicioEnvioWhatsapp $envio,
        private readonly PuertaEnlaceWhatsapp $puertaEnlace,
    ) {}

    /**
     * Saluda al cliente identificado y conserva la respuesta en su historial.
     */
    public function saludarCliente(Conversacion $conversacion, Cliente $cliente): Mensaje
    {
        $nombre = trim($cliente->nombre_razon_social);
        $saludo = "Hola, {$nombre}. Soy el asistente virtual de Villafañe Wifi.\n\n"
            ."Elegí una opción respondiendo con su número:\n"
            .$this->crearMenu();

        return $this->envio->enviarTextoDelBot($conversacion, $saludo);
    }

    /**
     * Construye el menú desde el enumerado para mantener las opciones en un
     * único lugar y reutilizarlas cuando se interprete la respuesta.
     */
    private function crearMenu(): string
    {
        return collect(OpcionMenuWhatsapp::cases())
            ->map(fn (OpcionMenuWhatsapp $opcion): string => "{$opcion->value}. {$opcion->descripcion()}")
            ->implode("\n");
    }

    /**
     * Informa al contacto desconocido cómo continuar sin crear un cliente falso.
     */
    public function informarClienteNoIdentificado(string $numeroWhatsapp): string
    {
        $respuesta = 'Hola. No pudimos identificarte como cliente de Villafañe Wifi con este número. '
            .'Por favor, comunicate con administración para actualizar o registrar tu número de WhatsApp.';

        return $this->puertaEnlace->enviarTexto($numeroWhatsapp, $respuesta);
    }
}
