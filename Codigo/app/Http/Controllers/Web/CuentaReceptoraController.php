<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarCuentaReceptoraRequest;
use App\Models\CuentaReceptora;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CuentaReceptoraController: Controlador web para la gestion de cuentas bancarias y billeteras virtuales oficiales.
 * Corresponde a los casos de uso: 'Gestionar cuentas receptoras', 'Registrar y activar cuenta'
 * y 'Desactivar cuenta receptora' (Modulo 1).
 * Conecta con el modelo CuentaReceptora (metodos activar(), desactivar()).
 */
class CuentaReceptoraController extends Controller
{
    /**
     * Muestra el listado de cuentas bancarias y billeteras oficiales configuradas.
     */
    public function index(): View
    {
        return view('cuentas-receptoras.index', ['cuentas' => CuentaReceptora::orderBy('nombre')->get()]);
    }

    /**
     * Registra una nueva cuenta bancaria o CBU/CVU receptora de cobros.
     */
    public function store(GuardarCuentaReceptoraRequest $request): RedirectResponse
    {
        CuentaReceptora::create($request->validated());

        return back()->with('exito', 'Cuenta receptora registrada.');
    }

    /**
     * Actualiza los datos identificadores o estado de la cuenta receptora.
     */
    public function update(GuardarCuentaReceptoraRequest $request, CuentaReceptora $cuentaReceptora): RedirectResponse
    {
        $cuentaReceptora->update($request->validated());

        return back()->with('exito', 'Cuenta receptora actualizada.');
    }
}
