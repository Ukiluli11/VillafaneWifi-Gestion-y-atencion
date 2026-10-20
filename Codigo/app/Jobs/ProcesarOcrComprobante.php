<?php

namespace App\Jobs;

use App\Dominio\OCRService;
use App\Models\Comprobante;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcesarOcrComprobante implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $idComprobante)
    {
        $this->onQueue('ocr');
    }

    public function handle(OCRService $ocr): void
    {
        $comprobante = Comprobante::find($this->idComprobante);
        if ($comprobante === null || ! $comprobante->estaPendiente()) {
            return;
        }
        if (! $ocr->estaConfigurado()) {
            $comprobante->update(['estado_ocr' => 'desactivado']);

            return;
        }
        if (! Comprobante::whereKey($this->idComprobante)->where('estado_validacion', 'pendiente')->where('estado_ocr', 'en_cola')->update(['estado_ocr' => 'procesando'])) {
            return;
        }
        try {
            $datos = $ocr->procesarArchivo(Storage::disk('local')->path($comprobante->ruta_archivo));
            DB::transaction(function () use ($datos, $ocr): void {
                $actual = Comprobante::whereKey($this->idComprobante)->lockForUpdate()->firstOrFail();
                if (! $actual->estaPendiente()) {
                    return;
                }
                $actual->update([
                    'numero_operacion' => $actual->numero_operacion ?? $datos['numero_operacion'],
                    'monto_ocr' => $actual->monto_ocr ?? $datos['monto'],
                    'fecha_ocr' => $actual->fecha_ocr ?? $datos['fecha'],
                    'estado_ocr' => 'procesado',
                    'error_ocr' => null,
                    'completitud_ocr' => $ocr->calcularConfianza($datos),
                ]);
            });
        } catch (Throwable) {
            Comprobante::whereKey($this->idComprobante)->update([
                'estado_ocr' => 'fallido',
                'error_ocr' => 'No se pudo leer el archivo automáticamente. Revisá el original o reintentá la lectura.',
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Comprobante::whereKey($this->idComprobante)->where('estado_ocr', 'procesando')->update([
            'estado_ocr' => 'fallido', 'error_ocr' => 'La lectura se interrumpió. Podés reintentar desde el detalle.',
        ]);
    }
}
