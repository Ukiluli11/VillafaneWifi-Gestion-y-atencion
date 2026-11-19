<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Servicio;
use Illuminate\View\View;

/**
 * Prepara el inicio del panel según el usuario autenticado y sus permisos.
 */
class InicioController extends Controller
{
    public function __invoke(): View
    {
        $cuotasPorEstado = Cuota::query()
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->selectRaw('estado, COUNT(*) AS cantidad, SUM(monto) AS importe')
            ->groupBy('estado')
            ->get()
            ->keyBy(fn (Cuota $cuota): string => $cuota->estado->value);
        $pendientes = $cuotasPorEstado->get(EstadoCuota::Pendiente->value);
        $vencidas = $cuotasPorEstado->get(EstadoCuota::Vencida->value);

        return view('inicio', [
            'clientesActivos' => Cliente::where('estado', 'activo')->count(),
            'serviciosActivos' => Servicio::where('estado', EstadoServicio::Activo->value)->count(),
            'cuotasPorCobrar' => [
                'pendientes' => ['cantidad' => (int) ($pendientes?->cantidad ?? 0), 'importe' => (float) ($pendientes?->importe ?? 0)],
                'vencidas' => ['cantidad' => (int) ($vencidas?->cantidad ?? 0), 'importe' => (float) ($vencidas?->importe ?? 0)],
            ],
        ]);
    }
}
