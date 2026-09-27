<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarCuentaReceptoraRequest;
use App\Models\CuentaReceptora;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CuentaReceptoraController extends Controller
{
    public function index(): View
    {
        return view('cuentas-receptoras.index', ['cuentas' => CuentaReceptora::orderBy('nombre')->get()]);
    }

    public function store(GuardarCuentaReceptoraRequest $request): RedirectResponse
    {
        CuentaReceptora::create($request->validated());

        return back()->with('exito', 'Cuenta receptora registrada.');
    }

    public function update(GuardarCuentaReceptoraRequest $request, CuentaReceptora $cuentaReceptora): RedirectResponse
    {
        $cuentaReceptora->update($request->validated());

        return back()->with('exito', 'Cuenta receptora actualizada.');
    }
}
