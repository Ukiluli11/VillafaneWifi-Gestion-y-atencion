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

/**
 * Genera cuotas de los servicios contratados y registra los pagos correspondientes.
 */
class ServicioFacturacion
{
    /**
     * Caso de Uso: Generar cuotas mensuales (Módulo 1)
     * Clase UML: Cuota | Método UML: + marcarComoVencida() : void
     * Servicio coordinador: ServicioFacturacion::generarCuota()
     * Requerimiento: RF-07
     */
    public function generarCuota(Servicio $servicio, string $periodo): Cuota
    {
        $servicio->loadMissing('plan');

        if ($servicio->estado === EstadoServicio::Baja) {
            throw ValidationException::withMessages(['periodo' => 'No se generan cuotas para servicios dados de baja.']);
        }

        $primerDia = $this->inicioPeriodo($periodo);

        $vencimiento = $primerDia->day(min($servicio->dia_vencimiento, $primerDia->daysInMonth));
        $alta = CarbonImmutable::parse($servicio->fecha_alta->toDateString(), $primerDia->timezone);
        if ($alta->greaterThan($primerDia->endOfMonth())) {
            throw ValidationException::withMessages(['periodo' => 'El servicio aún no estaba contratado en ese período.']);
        }

        $emision = $alta->greaterThan($primerDia) ? $alta : $primerDia;
        $vencimiento = $vencimiento->greaterThanOrEqualTo($emision) ? $vencimiento : $emision;
        $estado = $vencimiento->isBefore(CarbonImmutable::today($primerDia->timezone)) ? EstadoCuota::Vencida : EstadoCuota::Pendiente;

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

    /**
     * Caso de Uso: Generar cuotas mensuales (Módulo 1)
     * Servicio coordinador: ServicioFacturacion::generarParaServiciosActivos()
     * Requerimiento: RF-07
     */
    public function generarParaServiciosActivos(string $periodo): int
    {
        $creadas = 0;
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        Servicio::query()->where('estado', EstadoServicio::Activo->value)
            ->whereDate('fecha_alta', '<=', min($hoy, $this->inicioPeriodo($periodo)->endOfMonth())->toDateString())
            ->whereDoesntHave('cuotas', fn ($cuotas) => $cuotas->where('periodo', $periodo))->chunkById(
                100,
                function (Collection $servicios) use ($periodo, &$creadas): void {
                    foreach ($servicios as $servicio) {
                        $creadas += DB::transaction(function () use ($servicio, $periodo): int {
                            $actual = Servicio::query()->lockForUpdate()->findOrFail($servicio->getKey());
                            if ($actual->estado !== EstadoServicio::Activo || $actual->fecha_alta->format('Y-m') > $periodo) {
                                return 0;
                            }

                            return $this->generarCuota($actual, $periodo)->wasRecentlyCreated ? 1 : 0;
                        }, 3);
                    }
                },
                'id_servicio',
            );

        return $creadas;
    }

    private function inicioPeriodo(string $periodo): CarbonImmutable
    {
        if (! preg_match('/\A\d{4}-(?:0[1-9]|1[0-2])\z/', $periodo)) {
            throw ValidationException::withMessages(['periodo' => 'El período debe usar el formato AAAA-MM.']);
        }

        return CarbonImmutable::createFromFormat('!Y-m', $periodo, 'America/Argentina/Buenos_Aires');
    }

    /** @param list<int> $identificadoresCuota */
    public function registrarPago(array $identificadoresCuota, CuentaReceptora $cuenta, MedioPago $medio, string $fecha, ?int $idComprobante = null): Pago
    {
        if ($identificadoresCuota === []) {
            throw ValidationException::withMessages(['cuotas' => 'Debe seleccionar al menos una cuota.']);
        }

        return DB::transaction(function () use ($identificadoresCuota, $cuenta, $medio, $fecha, $idComprobante): Pago {
            $cuenta = CuentaReceptora::query()->lockForUpdate()->findOrFail($cuenta->getKey());
            $this->validarCuentaReceptora($cuenta);

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
            if ($monto->isLessThanOrEqualTo(0) || $monto->isGreaterThan('99999999.99')) {
                throw ValidationException::withMessages(['cuotas' => 'El importe total del pago está fuera del rango admitido.']);
            }
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
     * Caso de Uso: Imputar pago a cuotas pendientes (Módulo 3)
     * Clase UML: Pago | Método UML: + imputar(cuotas: List) : void
     * Servicio coordinador: ServicioFacturacion::imputarPagoACuotas()
     * Requerimientos: RF-25, RF-06
     *
     * Imputa un monto a las cuotas impagas del cliente en orden cronológico (las más antiguas primero).
     */
    public function imputarPagoACuotas(
        Cliente $cliente,
        CuentaReceptora $cuenta,
        MedioPago $medio,
        string $fecha,
        string|float|BigDecimal $montoTotal,
        ?int $idComprobante = null
    ): Pago {
        $this->validarCuentaReceptora($cuenta);

        $montoDisponible = $montoTotal instanceof BigDecimal ? $montoTotal : BigDecimal::of((string) $montoTotal);
        if ($montoDisponible->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages(['monto' => 'El monto debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($cliente, $cuenta, $medio, $fecha, $montoDisponible, $idComprobante): Pago {
            // Buscamos todas las cuotas adeudadas ordenadas cronológicamente
            $cuotasImpagas = Cuota::query()
                ->whereHas('servicio', fn ($q) => $q->where('id_cliente', $cliente->id_cliente))
                ->whereNull('id_pago')
                ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
                ->orderBy('fecha_vencimiento', 'asc')
                ->orderBy('id_cuota', 'asc')
                ->lockForUpdate()
                ->get();

            $montoRestante = $montoDisponible;
            $cuotasACancelar = [];

            foreach ($cuotasImpagas as $cuota) {
                $montoCuota = BigDecimal::of($cuota->monto);
                if ($montoRestante->isGreaterThanOrEqualTo($montoCuota)) {
                    $cuotasACancelar[] = $cuota->id_cuota;
                    $montoRestante = $montoRestante->minus($montoCuota);
                } else {
                    break;
                }
            }

            if ($cuotasACancelar === [] || ! $montoRestante->isZero()) {
                throw ValidationException::withMessages([
                    'monto' => 'El importe debe cancelar exactamente una o más cuotas completas, comenzando por las más antiguas. Por ahora no se admiten pagos parciales ni saldos a favor.',
                ]);
            }
            $pago = Pago::create([
                'id_cuenta' => $cuenta->id_cuenta,
                'id_comprobante' => $idComprobante,
                'fecha' => $fecha,
                'monto_total' => (string) $montoDisponible,
                'medio_pago' => $medio,
            ]);
            Cuota::whereIn('id_cuota', $cuotasACancelar)->update([
                'id_pago' => $pago->id_pago,
                'estado' => EstadoCuota::Pagada->value,
            ]);

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

    private function validarCuentaReceptora(CuentaReceptora $cuenta): void
    {
        if ($cuenta->estado !== EstadoCuentaReceptora::Activa) {
            throw ValidationException::withMessages(['id_cuenta' => 'La cuenta receptora está inactiva.']);
        }
        if (config('services.transferencias.habilitadas') && $cuenta->id_cuenta !== (int) config('services.transferencias.id_cuenta')) {
            throw ValidationException::withMessages(['id_cuenta' => 'Debe utilizar la cuenta de Mercado Pago configurada para la empresa.']);
        }
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
