<?php

namespace App\Dominio;

use App\Enums\EstadoCuentaReceptora;
use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Enums\MedioPago;
use App\Models\Cliente;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Servicio;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioFacturacion
{
    public function generarCuota(Servicio $servicio, string $periodo): Cuota
    {
        $servicio->loadMissing('plan');

        if ($servicio->estado === EstadoServicio::Baja) {
            throw ValidationException::withMessages(['periodo' => 'No se generan cuotas para servicios dados de baja.']);
        }

        $primerDia = CarbonImmutable::createFromFormat('!Y-m', $periodo);
        if (! $primerDia || $primerDia->format('Y-m') !== $periodo) {
            throw ValidationException::withMessages(['periodo' => 'El período debe usar el formato AAAA-MM.']);
        }

        $vencimiento = $primerDia->day(min($servicio->dia_vencimiento, $primerDia->daysInMonth));
        $alta = CarbonImmutable::parse($servicio->fecha_alta);
        if ($alta->greaterThan($vencimiento)) {
            throw ValidationException::withMessages(['periodo' => 'El servicio aún no estaba contratado en ese período.']);
        }

        $emision = $alta->greaterThan($primerDia) ? $alta : $primerDia;
        $vencimiento = $vencimiento->greaterThanOrEqualTo($emision) ? $vencimiento : $emision;
        $estado = $vencimiento->isBefore(CarbonImmutable::today()) ? EstadoCuota::Vencida : EstadoCuota::Pendiente;

        $cuota = Cuota::firstOrCreate(
            ['id_servicio' => $servicio->id_servicio, 'periodo' => $periodo],
            [
                'monto' => $servicio->plan->precio_vigente,
                'fecha_emision' => $emision,
                'fecha_vencimiento' => $vencimiento,
                'estado' => $estado,
            ],
        );
        $this->actualizarProximoVencimiento($servicio);

        return $cuota;
    }

    public function generarParaServiciosActivos(string $periodo): int
    {
        $creadas = 0;
        Servicio::query()->where('estado', EstadoServicio::Activo->value)->with('plan')->chunkById(
            100,
            function (Collection $servicios) use ($periodo, &$creadas): void {
                foreach ($servicios as $servicio) {
                    $existia = Cuota::where('id_servicio', $servicio->id_servicio)->where('periodo', $periodo)->exists();
                    try {
                        $this->generarCuota($servicio, $periodo);
                        $creadas += $existia ? 0 : 1;
                    } catch (ValidationException $error) {
                        $mensajePeriodo = $error->errors()['periodo'][0] ?? '';
                        if (! str_contains($mensajePeriodo, 'aún no estaba contratado')) {
                            throw $error;
                        }
                    }
                }
            },
            'id_servicio',
        );

        return $creadas;
    }

    /** @param list<int> $identificadoresCuota */
    public function registrarPago(array $identificadoresCuota, CuentaReceptora $cuenta, MedioPago $medio, string $fecha, ?int $idComprobante = null): Pago
    {
        if ($identificadoresCuota === []) {
            throw ValidationException::withMessages(['cuotas' => 'Debe seleccionar al menos una cuota.']);
        }
        if ($cuenta->estado !== EstadoCuentaReceptora::Activa) {
            throw ValidationException::withMessages(['id_cuenta' => 'La cuenta receptora está inactiva.']);
        }

        return DB::transaction(function () use ($identificadoresCuota, $cuenta, $medio, $fecha, $idComprobante): Pago {
            $cuotas = Cuota::query()
                ->whereIn('id_cuota', array_unique($identificadoresCuota))
                ->with('servicio')
                ->lockForUpdate()
                ->get();
            if ($cuotas->count() !== count(array_unique($identificadoresCuota))) {
                throw ValidationException::withMessages(['cuotas' => 'Alguna cuota seleccionada no existe.']);
            }
            if ($cuotas->contains(fn (Cuota $cuota): bool => $cuota->id_pago !== null)) {
                throw ValidationException::withMessages(['cuotas' => 'Alguna cuota ya se encuentra pagada.']);
            }
            if ($cuotas->pluck('servicio.id_cliente')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['cuotas' => 'Un pago sólo puede cancelar cuotas de un cliente.']);
            }

            $monto = $cuotas->reduce(
                fn (BigDecimal $total, Cuota $cuota): BigDecimal => $total->plus($cuota->monto),
                BigDecimal::zero(),
            );
            $pago = Pago::create([
                'id_cuenta' => $cuenta->id_cuenta,
                'id_comprobante' => $idComprobante,
                'fecha' => $fecha,
                'monto_total' => (string) $monto,
                'medio_pago' => $medio,
            ]);
            Cuota::whereIn('id_cuota', $cuotas->pluck('id_cuota'))->update([
                'id_pago' => $pago->id_pago,
                'estado' => EstadoCuota::Pagada->value,
            ]);
            $cuotas->pluck('servicio')->unique('id_servicio')->each(
                fn (Servicio $servicio) => $this->actualizarProximoVencimiento($servicio),
            );

            return $pago->load(['cuenta', 'cuotas.servicio', 'comprobante']);
        });
    }

    /**
     * Imputa un monto a las cuotas impagas del cliente en orden cronológico (las más antiguas primero - RF-21).
     */
    public function imputarPagoACuotas(
        Cliente $cliente,
        CuentaReceptora $cuenta,
        MedioPago $medio,
        string $fecha,
        string|float|BigDecimal $montoTotal,
        ?int $idComprobante = null
    ): Pago {
        if ($cuenta->estado !== EstadoCuentaReceptora::Activa) {
            throw ValidationException::withMessages(['id_cuenta' => 'La cuenta receptora está inactiva.']);
        }

        $montoDisponible = $montoTotal instanceof BigDecimal ? $montoTotal : BigDecimal::of((string) $montoTotal);
        if ($montoDisponible->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages(['monto' => 'El monto debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($cliente, $cuenta, $medio, $fecha, $montoDisponible, $idComprobante): Pago {
            // Buscamos todas las cuotas adeudadas ordenadas cronológicamente (RF-21)
            $cuotasImpagas = Cuota::query()
                ->whereHas('servicio', fn ($q) => $q->where('id_cliente', $cliente->id_cliente))
                ->whereNull('id_pago')
                ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
                ->orderBy('fecha_vencimiento', 'asc')
                ->orderBy('id_cuota', 'asc')
                ->lockForUpdate()
                ->get();

            $pago = Pago::create([
                'id_cuenta' => $cuenta->id_cuenta,
                'id_comprobante' => $idComprobante,
                'fecha' => $fecha,
                'monto_total' => (string) $montoDisponible,
                'medio_pago' => $medio,
            ]);

            $montoRestante = $montoDisponible;

            foreach ($cuotasImpagas as $cuota) {
                $montoCuota = BigDecimal::of($cuota->monto);
                if ($montoRestante->isGreaterThanOrEqualTo($montoCuota)) {
                    $cuota->update([
                        'id_pago' => $pago->id_pago,
                        'estado' => EstadoCuota::Pagada->value,
                    ]);
                    $montoRestante = $montoRestante->minus($montoCuota);
                } else {
                    break;
                }
            }

            $servicios = Servicio::where('id_cliente', $cliente->id_cliente)->get();
            foreach ($servicios as $servicio) {
                $this->actualizarProximoVencimiento($servicio);
            }

            return $pago->load(['cuenta', 'cuotas.servicio', 'comprobante']);
        });
    }

    public function actualizarEstadosVencidos(): int
    {
        return Cuota::query()
            ->where('estado', EstadoCuota::Pendiente->value)
            ->whereNull('id_pago')
            ->whereDate('fecha_vencimiento', '<', CarbonImmutable::today())
            ->update(['estado' => EstadoCuota::Vencida->value]);
    }

    private function actualizarProximoVencimiento(Servicio $servicio): void
    {
        $proxima = $servicio->cuotas()
            ->whereNull('id_pago')
            ->whereDate('fecha_vencimiento', '>=', CarbonImmutable::today())
            ->orderBy('fecha_vencimiento')
            ->value('fecha_vencimiento');
        $servicio->update(['proximo_vencimiento' => $proxima]);
    }
}
