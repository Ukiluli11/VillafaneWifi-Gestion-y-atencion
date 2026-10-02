<?php

namespace App\Dominio;

use App\Enums\EstadoComprobante;
use App\Enums\MedioPago;
use App\Enums\OrigenComprobante;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\CuentaReceptora;
use App\Models\Pago;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServicioComprobantes
{
    public function __construct(
        private readonly ServicioFacturacion $servicioFacturacion,
        private readonly OCRService $ocrService,
        private readonly NotificacionService $notificacionService,
    ) {}

    /**
     * Registra un nuevo comprobante en el sistema, extrayendo datos con OCR y calculando el hash antifraude (RF-19, RF-20, RF-23).
     */
    public function registrarComprobante(
        Cliente $cliente,
        UploadedFile|string $archivo,
        ?string $numeroOperacionManual = null,
        ?string $montoManual = null,
        ?string $fechaManual = null,
        OrigenComprobante $origen = OrigenComprobante::PanelManual,
    ): Comprobante {
        $esUploadedFile = $archivo instanceof UploadedFile;

        // 1. Cálculo de Hash SHA-256 para verificación antifraude (RF-20)
        $hash = $esUploadedFile
            ? hash_file('sha256', $archivo->getRealPath())
            : hash('sha256', $archivo);

        if (Comprobante::where('hash_archivo', $hash)->exists()) {
            throw ValidationException::withMessages([
                'comprobante' => 'El comprobante ya fue registrado previamente en el sistema (detección antifraude por hash SHA-256).',
            ]);
        }

        // 2. Extracción por OCR si los datos no fueron provistos manualmente (RF-23)
        $rutaFisica = $esUploadedFile ? $archivo->getRealPath() : null;
        $datosOcr = $this->ocrService->procesarArchivo(
            $rutaFisica ?? '',
            $esUploadedFile ? null : (is_string($archivo) && ! file_exists($archivo) ? $archivo : null)
        );

        $numeroOperacion = $numeroOperacionManual ?: $datosOcr['numero_operacion'];
        $monto = $montoManual ?: $datosOcr['monto'];
        $fecha = $fechaManual ?: ($datosOcr['fecha'] ?: CarbonImmutable::today()->toDateString());

        // 3. Almacenamiento seguro del archivo
        $rutaGuardada = null;
        if ($esUploadedFile) {
            $nombreArchivo = $hash . '.' . $archivo->getClientOriginalExtension();
            $rutaGuardada = $archivo->storeAs('comprobantes', $nombreArchivo, 'public');
        }

        // 4. Creación del registro en base de datos con estado Pendiente
        return Comprobante::create([
            'id_cliente' => $cliente->id_cliente,
            'hash_archivo' => $hash,
            'numero_operacion' => $numeroOperacion,
            'monto_ocr' => $monto,
            'fecha_ocr' => $fecha,
            'ruta_archivo' => $rutaGuardada,
            'estado_validacion' => EstadoComprobante::Pendiente,
            'origen' => $origen,
        ]);
    }

   
    public function conciliarYAprobar(
        Comprobante $comprobante,
        CuentaReceptora $cuenta,
        MedioPago $medio,
        ?string $fechaPago = null,
        ?string $montoAprobado = null,
        ?Usuario $usuario = null,
    ): Pago {
        if (! $comprobante->estaPendiente()) {
            throw ValidationException::withMessages([
                'comprobante' => "El comprobante ya fue evaluado anteriormente (Estado: {$comprobante->estado_validacion->value}).",
            ]);
        }

        $montoFinal = $montoAprobado ?: (string) $comprobante->monto_ocr;
        if (empty($montoFinal) || (float) $montoFinal <= 0) {
            throw ValidationException::withMessages([
                'monto' => 'Debe indicar un monto válido para imputar y aprobar el comprobante.',
            ]);
        }

        $fechaFinal = $fechaPago ?: ($comprobante->fecha_ocr?->toDateString() ?: CarbonImmutable::today()->toDateString());

        return DB::transaction(function () use ($comprobante, $cuenta, $medio, $fechaFinal, $montoFinal, $usuario): Pago {
            
            $pago = $this->servicioFacturacion->imputarPagoACuotas(
                $comprobante->cliente,
                $cuenta,
                $medio,
                $fechaFinal,
                $montoFinal,
                $comprobante->id_comprobante
            );

            
            $comprobante->update([
                'id_pago' => $pago->id_pago,
                'id_usuario' => $usuario?->id_usuario,
                'fecha_hora_validacion' => CarbonImmutable::now(),
                'estado_validacion' => EstadoComprobante::Aprobado,
                'motivo_rechazo' => null,
            ]);

            
            $this->notificacionService->notificarPagoAprobado($comprobante->cliente, $pago, $comprobante);

            return $pago;
        });
    }

   
    public function rechazarComprobante(Comprobante $comprobante, string $motivo, ?Usuario $usuario = null): Comprobante
    {
        if (! $comprobante->estaPendiente()) {
            throw ValidationException::withMessages([
                'comprobante' => "El comprobante ya fue evaluado anteriormente (Estado: {$comprobante->estado_validacion->value}).",
            ]);
        }

        $motivoLimpio = trim($motivo);
        if (empty($motivoLimpio)) {
            throw ValidationException::withMessages([
                'motivo_rechazo' => 'Debe ingresar un motivo para rechazar el comprobante.',
            ]);
        }

        $comprobante->update([
            'id_usuario' => $usuario?->id_usuario,
            'fecha_hora_validacion' => CarbonImmutable::now(),
            'estado_validacion' => EstadoComprobante::Rechazado,
            'motivo_rechazo' => $motivoLimpio,
        ]);

        
        $this->notificacionService->notificarPagoRechazado($comprobante->cliente, $comprobante, $motivoLimpio);

        return $comprobante;
    }
}
