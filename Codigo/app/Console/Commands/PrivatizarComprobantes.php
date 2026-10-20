<?php

namespace App\Console\Commands;

use App\Models\Comprobante;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('comprobantes:privatizar')]
#[Description('Traslada los comprobantes históricos públicos al almacenamiento privado verificando su integridad')]
class PrivatizarComprobantes extends Command
{
    public function handle(): int
    {
        $fallos = 0;
        Comprobante::whereNotNull('ruta_archivo')->chunkById(100, function ($comprobantes) use (&$fallos): void {
            foreach ($comprobantes as $comprobante) {
                $ruta = $comprobante->ruta_archivo;
                try {
                    if (! str_starts_with($ruta, 'comprobantes/') || str_contains($ruta, '..')) {
                        throw new \RuntimeException('Ruta inválida.');
                    }
                    $publico = Storage::disk('public');
                    $privado = Storage::disk('local');
                    if ($publico->exists($ruta)) {
                        $contenido = $publico->get($ruta);
                        if ($contenido === null || hash('sha256', $contenido) !== $comprobante->hash_archivo) {
                            throw new \RuntimeException('El archivo público no coincide con el hash registrado.');
                        }
                        if (! $privado->exists($ruta) && ! $privado->put($ruta, $contenido)) {
                            throw new \RuntimeException('No se pudo copiar el archivo.');
                        }
                        if (hash('sha256', $privado->get($ruta) ?? '') !== $comprobante->hash_archivo) {
                            throw new \RuntimeException('No se pudo verificar la copia privada.');
                        }
                        $comprobante->update(['disco_archivo' => 'local']);
                        if (! $publico->delete($ruta)) {
                            throw new \RuntimeException('No se pudo retirar la copia pública.');
                        }
                    } elseif (! $privado->exists($ruta)) {
                        throw new \RuntimeException('No se encontró el archivo.');
                    }
                } catch (Throwable $error) {
                    $fallos++;
                    $this->error('Comprobante #'.$comprobante->id_comprobante.': '.$error->getMessage());
                }
            }
        }, 'id_comprobante');
        $this->info('Traslado terminado. Archivos con problemas: '.$fallos);

        return $fallos === 0 ? self::SUCCESS : self::FAILURE;
    }
}
