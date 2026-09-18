<?php

namespace App\Dominio;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

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
}
