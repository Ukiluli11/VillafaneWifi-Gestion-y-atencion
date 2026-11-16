<?php

namespace App\Dominio;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicioPlanes
{
    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Plan
    {
        return DB::transaction(fn (): Plan => Plan::create($datos));
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Plan $plan, array $datos): Plan
    {
        return DB::transaction(function () use ($plan, $datos): Plan {
            $plan->update($datos);

            return $plan->refresh();
        });
    }

    public function desactivar(Plan $plan): Plan
    {
        $plan->desactivar();

        return $plan->refresh();
    }

    public function activar(Plan $plan): Plan
    {
        $plan->activar();

        return $plan->refresh();
    }

    public function eliminar(Plan $plan): void
    {
        DB::transaction(function () use ($plan): void {
            $plan = Plan::query()->lockForUpdate()->findOrFail($plan->getKey());
            if ($plan->servicios()->exists()) {
                throw ValidationException::withMessages([
                    'plan' => 'El plan tiene servicios asociados. Podés desactivarlo para retirarlo del catálogo.',
                ]);
            }

            $plan->delete();
        }, 3);
    }
}
