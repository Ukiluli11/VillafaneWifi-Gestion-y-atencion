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
use Throwable;

class ServicioComprobantes
{
    public function __construct(
        private readonly ServicioFacturacion $servicioFacturacion,
        private readonly OCRService $ocrService,
        private readonly NotificacionService $notificacionService,
    ) {}

    public function registrarComprobante(
        Cliente $cliente,
        UploadedFile|string $archivo,
        ?string $numeroOperacionManual = null,
        ?string $montoManual = null,
        ?string $fechaManual = null,
        OrigenComprobante $origen = OrigenComprobante::PanelManual,
    ): Comprobante {
        $esArchivo = $archivo instanceof UploadedFile;
        $hash = $esArchivo ? hash_file('sha256', $archivo->getRealPath()) : hash('sha256', $archivo);
        if (Comprobante::where('hash_archivo', $hash)->exists()) {
            throw ValidationException::withMessages(['comprobante' => 'El archivo ya fue registrado previamente. Buscá el comprobante existente.']);
        }
        $ruta = null;
        if ($esArchivo) {
            $ruta = $archivo->storeAs('comprobantes/'.$cliente->id_cliente, $hash.'.'.$archivo->extension(), 'local');
            if ($ruta === false) {
                throw ValidationException::withMessages(['archivo' => 'No se pudo guardar el archivo. Intentá nuevamente.']);
            }
        }
        try {
            $comprobante = Comprobante::create([
                'id_cliente' => $cliente->id_cliente,
                'hash_archivo' => $hash,
                'numero_operacion' => $numeroOperacionManual,
                'monto_ocr' => $montoManual,
                'fecha_ocr' => $fechaManual,
                'ruta_archivo' => $ruta,
                'disco_archivo' => 'local',
                'fecha_recepcion' => now(),
                'nombre_original' => $esArchivo ? $archivo->getClientOriginalName() : null,
                'mime_type' => $esArchivo ? $archivo->getMimeType() : null,
                'tamanio_bytes' => $esArchivo ? $archivo->getSize() : null,
                'estado_validacion' => EstadoComprobante::Pendiente,
                'origen' => $origen,
                'estado_ocr' => $this->ocrService->estaConfigurado() && $ruta !== null ? 'en_cola' : 'desactivado',
            ]);
        } catch (Throwable $error) {
            if ($ruta !== null && ! Comprobante::where('ruta_archivo', $ruta)->exists()) {
                Storage::disk('local')->delete($ruta);
            }
            if (Comprobante::where('hash_archivo', $hash)->exists()) {
                throw ValidationException::withMessages(['comprobante' => 'El archivo ya fue registrado previamente.']);
            }
            throw $error;
        }
        if ($comprobante->estado_ocr === 'en_cola') {
            $this->ocrService->encolarLectura($comprobante);
        }

        return $comprobante;
    }

    public function conciliarYAprobar(
        Comprobante $comprobante,
        CuentaReceptora $cuenta,
        MedioPago $medio,
        ?string $fechaPago = null,
        ?string $montoAprobado = null,
        ?Usuario $usuario = null,
        ?string $numeroOperacion = null,
    ): Pago {
        $pago = DB::transaction(function () use ($comprobante, $cuenta, $medio, $fechaPago, $montoAprobado, $usuario, $numeroOperacion): Pago {
            $actual = Comprobante::whereKey($comprobante->id_comprobante)->lockForUpdate()->firstOrFail();
            $this->exigirPendiente($actual);
            if ($usuario === null) {
                throw ValidationException::withMessages(['comprobante' => 'Debe quedar registrado el usuario responsable.']);
            }
            $monto = $montoAprobado ?: (string) $actual->monto_ocr;
            if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $monto) || (float) $monto <= 0) {
                throw ValidationException::withMessages(['monto' => 'Indicá un monto positivo con hasta dos decimales.']);
            }
            $fecha = $fechaPago ?: $actual->fecha_ocr?->toDateString();
            if ($fecha === null) {
                throw ValidationException::withMessages(['fecha' => 'Indicá la fecha que figura en el comprobante.']);
            }
            // Serializa la validación de operaciones contra la misma cuenta receptora.
            $cuentaActual = CuentaReceptora::whereKey($cuenta->id_cuenta)->lockForUpdate()->firstOrFail();
            $operacion = filled($numeroOperacion) ? mb_strtoupper(trim($numeroOperacion)) : $actual->numero_operacion;
            if (blank($operacion)) {
                throw ValidationException::withMessages(['numero_operacion' => 'Indicá el número de operación del comprobante.']);
            }
            $operacionNormalizada = $this->normalizarOperacion($operacion);
            if ($operacionNormalizada === '') {
                throw ValidationException::withMessages(['numero_operacion' => 'El número de operación debe contener letras o números.']);
            }
            $coincidencias = Comprobante::query()->where('estado_validacion', EstadoComprobante::Aprobado->value)
                ->whereKeyNot($actual->id_comprobante)
                ->whereHas('pago', fn ($consulta) => $consulta->where('id_cuenta', $cuentaActual->id_cuenta))
                ->get(['numero_operacion']);
            if ($coincidencias->contains(fn (Comprobante $otro): bool => $this->normalizarOperacion((string) $otro->numero_operacion) === $operacionNormalizada)) {
                throw ValidationException::withMessages(['comprobante' => 'Ya existe un pago aprobado con esa operación en la cuenta receptora. Revisá el comprobante anterior.']);
            }
            $pago = $this->servicioFacturacion->imputarPagoACuotas($actual->cliente, $cuentaActual, $medio, $fecha, $monto, $actual->id_comprobante);
            $actual->update([
                'numero_operacion' => $operacion,
                'id_pago' => $pago->id_pago,
                'id_usuario' => $usuario->id_usuario,
                'fecha_hora_validacion' => CarbonImmutable::now(),
                'estado_validacion' => EstadoComprobante::Aprobado,
                'motivo_rechazo' => null,
            ]);

            return $pago;
        });
        $comprobante->refresh();
        $this->notificacionService->notificarPagoAprobado($comprobante->cliente, $pago, $comprobante);

        return $pago;
    }

    public function rechazarComprobante(Comprobante $comprobante, string $motivo, ?Usuario $usuario = null): Comprobante
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 3 || mb_strlen($motivo) > 255 || $usuario === null) {
            throw ValidationException::withMessages(['motivo_rechazo' => 'Indicá un motivo de 3 a 255 caracteres y un usuario responsable.']);
        }
        DB::transaction(function () use ($comprobante, $motivo, $usuario): void {
            $actual = Comprobante::whereKey($comprobante->id_comprobante)->lockForUpdate()->firstOrFail();
            $this->exigirPendiente($actual);
            $actual->update([
                'id_usuario' => $usuario->id_usuario,
                'fecha_hora_validacion' => CarbonImmutable::now(),
                'estado_validacion' => EstadoComprobante::Rechazado,
                'motivo_rechazo' => $motivo,
            ]);
        });
        $comprobante->refresh();
        $this->notificacionService->notificarPagoRechazado($comprobante->cliente, $comprobante, $motivo);

        return $comprobante;
    }

    private function exigirPendiente(Comprobante $comprobante): void
    {
        if (! $comprobante->estaPendiente()) {
            throw ValidationException::withMessages(['comprobante' => 'El comprobante ya fue evaluado anteriormente.']);
        }
    }

    private function normalizarOperacion(string $operacion): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($operacion)) ?? '';
    }
}
