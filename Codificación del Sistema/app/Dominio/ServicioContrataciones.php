<?php

namespace App\Dominio;

use App\Enums\EstadoCliente;
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
    /** @param array<string, mixed> $datos */
    public function crear(Cliente $cliente, array $datos): Servicio
    {
        if ($cliente->estado !== EstadoCliente::Activo) {
            throw ValidationException::withMessages(['id_cliente' => 'El cliente debe estar activo.']);
        }

        $plan = Plan::findOrFail($datos['id_plan']);
        if ($plan->estado !== EstadoPlan::Activo) {
            throw ValidationException::withMessages(['id_plan' => 'El plan seleccionado está inactivo.']);
        }

        return DB::transaction(function () use ($cliente, $datos): Servicio {
            $datos['id_cliente'] = $cliente->id_cliente;
            $datos['mac'] = $this->normalizarMac($datos['mac'] ?? null);
            $datos['proximo_vencimiento'] = $this->calcularProximoVencimiento((int) $datos['dia_vencimiento']);

            return Servicio::create($datos);
        });
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Servicio $servicio, array $datos): Servicio
    {
        $plan = isset($datos['id_plan']) ? Plan::findOrFail($datos['id_plan']) : $servicio->plan;
        $estado = $datos['estado'] ?? $servicio->estado->value;
        if ($plan->estado !== EstadoPlan::Activo && $estado === EstadoServicio::Activo->value) {
            throw ValidationException::withMessages(['id_plan' => 'Un servicio activo no puede usar un plan inactivo.']);
        }

        return DB::transaction(function () use ($servicio, $datos): Servicio {
            if (array_key_exists('mac', $datos)) {
                $datos['mac'] = $this->normalizarMac($datos['mac']);
            }
            if (isset($datos['dia_vencimiento'])) {
                $datos['proximo_vencimiento'] = $this->calcularProximoVencimiento((int) $datos['dia_vencimiento']);
            }
            $servicio->update($datos);

            return $servicio->refresh();
        });
    }

    public function suspender(Servicio $servicio): Servicio
    {
        $servicio->suspender();

        return $servicio->refresh();
    }

    public function reactivar(Servicio $servicio): Servicio
    {
        $servicio->loadMissing(['cliente', 'plan']);

        if ($servicio->cliente->estado !== EstadoCliente::Activo || $servicio->plan->estado !== EstadoPlan::Activo) {
            throw ValidationException::withMessages(['estado' => 'El cliente y el plan deben estar activos.']);
        }
        $servicio->reactivar();

        return $servicio->refresh();
    }

    private function normalizarMac(?string $mac): ?string
    {
        return $mac ? mb_strtoupper(str_replace('-', ':', trim($mac))) : null;
    }

    private function calcularProximoVencimiento(int $dia): CarbonImmutable
    {
        $hoy = CarbonImmutable::today();
        $candidato = $hoy->day(min($dia, $hoy->daysInMonth));

        return $candidato->lessThan($hoy)
            ? $hoy->addMonthNoOverflow()->day(min($dia, $hoy->addMonthNoOverflow()->daysInMonth))
            : $candidato;
    }
}
