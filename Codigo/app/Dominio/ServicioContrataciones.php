<?php

namespace App\Dominio;

use App\Enums\EstadoCliente;
use App\Enums\EstadoCuota;
use App\Enums\EstadoPlan;
use App\Enums\EstadoServicio;
use App\Models\Cliente;
use App\Models\Plan;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioContrataciones
{
    public function __construct(private readonly ServicioFacturacion $facturacion) {}

    /** @param array<string, mixed> $datos */
    public function crear(Cliente $cliente, array $datos, bool $emitirCuotaInicial = false): Servicio
    {
        return DB::transaction(function () use ($cliente, $datos, $emitirCuotaInicial): Servicio {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($cliente->getKey());
            if ($cliente->estado !== EstadoCliente::Activo) {
                throw ValidationException::withMessages(['id_cliente' => 'El cliente debe estar activo.']);
            }

            $plan = Plan::query()->lockForUpdate()->findOrFail($datos['id_plan']);
            if ($plan->estado !== EstadoPlan::Activo) {
                throw ValidationException::withMessages(['id_plan' => 'El plan seleccionado está inactivo.']);
            }

            $datos['id_cliente'] = $cliente->id_cliente;
            $datos['mac'] = $this->normalizarMac($datos['mac'] ?? null);
            $datos['proximo_vencimiento'] = $this->calcularProximoVencimiento((int) $datos['dia_vencimiento']);
            $servicio = Servicio::create($datos);
            if ($emitirCuotaInicial) {
                $this->facturacion->generarCuota($servicio, $servicio->fecha_alta->format('Y-m'));
            }

            return $servicio->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Servicio $servicio, array $datos): Servicio
    {
        return DB::transaction(function () use ($servicio, $datos): Servicio {
            $servicio = Servicio::query()->lockForUpdate()->findOrFail($servicio->getKey());
            $plan = Plan::query()->lockForUpdate()->findOrFail($datos['id_plan'] ?? $servicio->id_plan);
            $estado = $datos['estado'] ?? $servicio->estado->value;
            if ($estado !== $servicio->estado->value) {
                if ($servicio->estado === EstadoServicio::Baja) {
                    throw ValidationException::withMessages(['estado' => 'El servicio está dado de baja. Registrá una nueva contratación.']);
                }
                if ($estado === EstadoServicio::Baja->value) {
                    $this->verificarMora($servicio);
                }
            }
            if ($plan->estado !== EstadoPlan::Activo && $estado === EstadoServicio::Activo->value
                && ($plan->getKey() !== $servicio->id_plan || $servicio->estado !== EstadoServicio::Activo)) {
                throw ValidationException::withMessages(['id_plan' => 'Un servicio activo no puede usar un plan inactivo.']);
            }
            if ($estado === EstadoServicio::Activo->value && $servicio->cliente->estado !== EstadoCliente::Activo) {
                throw ValidationException::withMessages(['estado' => 'El cliente debe estar activo.']);
            }
            if (array_key_exists('mac', $datos)) {
                $datos['mac'] = $this->normalizarMac($datos['mac']);
            }
            if (isset($datos['dia_vencimiento']) && $estado !== EstadoServicio::Baja->value) {
                $datos['proximo_vencimiento'] = $this->calcularProximoVencimiento((int) $datos['dia_vencimiento']);
            }
            if ($estado === EstadoServicio::Baja->value) {
                $datos['proximo_vencimiento'] = null;
            }

            $servicio->update($datos);

            return $servicio->refresh();
        }, 3);
    }

    public function suspender(Servicio $servicio): Servicio
    {
        return $this->actualizar($servicio, ['estado' => EstadoServicio::Suspendido->value]);
    }

    public function reactivar(Servicio $servicio): Servicio
    {
        return $this->actualizar($servicio, ['estado' => EstadoServicio::Activo->value]);
    }

    public function darDeBajaPorMora(Servicio $servicio): Servicio
    {
        return $this->actualizar($servicio, ['estado' => EstadoServicio::Baja->value]);
    }

    private function verificarMora(Servicio $servicio): void
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $cuotasVencidas = $servicio->cuotas()->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->whereDate('fecha_vencimiento', '<', $hoy->toDateString())
            ->lockForUpdate()->get(['id_cuota'])->count();

        if ($cuotasVencidas < 2) {
            throw ValidationException::withMessages(['estado' => 'La baja por mora requiere al menos dos cuotas mensuales vencidas e impagas.']);
        }
    }

    private function normalizarMac(?string $mac): ?string
    {
        return $mac ? mb_strtoupper(str_replace('-', ':', trim($mac))) : null;
    }

    private function calcularProximoVencimiento(int $dia): CarbonImmutable
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $candidato = $hoy->day(min($dia, $hoy->daysInMonth));

        return $candidato->lessThan($hoy)
            ? $hoy->addMonthNoOverflow()->day(min($dia, $hoy->addMonthNoOverflow()->daysInMonth))
            : $candidato;
    }
}
