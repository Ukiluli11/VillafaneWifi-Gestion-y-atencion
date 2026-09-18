<?php

namespace App\Console\Commands;

use App\Infraestructura\Whatsapp\ClienteMetaWhatsapp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('whatsapp:probar-conexion {numero? : Destino internacional sin +} {--enviar : Envía un mensaje real de prueba}')]
#[Description('Valida las credenciales de WhatsApp Cloud API y opcionalmente envía un mensaje real')]
class ProbarConexionWhatsapp extends Command
{
    /** Comprueba la identidad del número y evita mostrar secretos en consola. */
    public function handle(ClienteMetaWhatsapp $cliente): int
    {
        if (config('services.whatsapp.modo_simulacion')) {
            $this->error('El modo simulación está activo. Configurá WHATSAPP_MODO_SIMULACION=false.');

            return self::FAILURE;
        }

        try {
            $numero = $cliente->consultarNumeroConfigurado();
            $this->table(
                ['Nombre verificado', 'Número', 'Phone Number ID', 'Calidad'],
                [[
                    $numero['verified_name'] ?: 'Sin informar',
                    $numero['display_phone_number'] ?: 'Sin informar',
                    $numero['id'],
                    $numero['quality_rating'] ?? 'Sin informar',
                ]],
            );

            if (! $this->option('enviar')) {
                $this->info('Conexión y credenciales verificadas correctamente.');

                return self::SUCCESS;
            }

            $destino = preg_replace('/\D+/', '', (string) $this->argument('numero')) ?? '';
            if ($destino === '') {
                $this->error('Indicá el número destinatario para utilizar --enviar.');

                return self::FAILURE;
            }

            $identificador = $cliente->enviarTexto(
                $destino,
                'Hola. Este es un mensaje de prueba del sistema de Villafañe Wifi.',
            );
            $this->info("Mensaje aceptado por Meta: {$identificador}");

            return self::SUCCESS;
        } catch (Throwable $error) {
            report($error);
            $this->error('No se pudo completar la prueba: '.$error->getMessage());

            return self::FAILURE;
        }
    }
}
