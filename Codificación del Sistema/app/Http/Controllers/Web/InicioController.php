<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Servicio;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        return view('inicio', [
            'clientesActivos' => Cliente::where('estado', 'activo')->count(),
            'serviciosActivos' => Servicio::where('estado', EstadoServicio::Activo->value)->count(),
            'cuotasPendientes' => Cuota::whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])->count(),
            'deudaVencida' => Cuota::where('estado', EstadoCuota::Vencida->value)->sum('monto'),
        ]);
    }
}
