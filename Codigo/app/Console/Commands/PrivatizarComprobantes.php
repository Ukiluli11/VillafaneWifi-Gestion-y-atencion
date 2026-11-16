<?php

namespace App\Console\Commands;

use App\Models\Comprobante;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('comprobantes:privatizar {--verificar : Comprueba el SHA-256 privado sin copiar ni borrar archivos}')]
#[Description('Traslada los comprobantes históricos públicos al almacenamiento privado verificando su integridad')]
class PrivatizarComprobantes extends Command
{
    public function handle(): int
    {
        $fallos = 0;
        $verificar = (bool) $this->option('verificar');
        $revisados = 0;
        Comprobante::whereNotNull('ruta_archivo')->chunkById(100, function ($comprobantes) use (&$fallos, &$revisados, $verificar): void {
            foreach ($comprobantes as $comprobante) {
                $revisados++;
                $ruta = $comprobante->ruta_archivo;
                try {
                    if (! str_starts_with($ruta, 'comprobantes/') || str_contains($ruta, '..')) {
                        throw new \RuntimeException('Ruta inválida.');
                    }
                    $privado = Storage::disk('local');
                    if ($verificar) {
                        if (! $privado->exists($ruta)) {
                            throw new \RuntimeException('Falta el archivo privado.');
                        }
                        $contenido = $privado->get($ruta);
                        if ($contenido === null || hash('sha256', $contenido) !== $comprobante->hash_archivo) {
                            throw new \RuntimeException('El archivo privado no coincide con el SHA-256 registrado.');
                        }

                        continue;
                    }
                    $publico = Storage::disk('public');
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
        $operacion = $verificar ? 'Verificación' : 'Traslado';
        $this->info("{$operacion}. Archivos revisados: {$revisados}. Con problemas: {$fallos}.");

        return $fallos === 0 ? self::SUCCESS : self::FAILURE;
    }
}
