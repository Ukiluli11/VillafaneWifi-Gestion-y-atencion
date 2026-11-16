<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoCuentaReceptora;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarCuentaReceptoraRequest;
use App\Models\CuentaReceptora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        return view('cuentas-receptoras.index', ['cuentas' => CuentaReceptora::withExists('pagos')->orderBy('nombre')->get()]);
    }

    /**
     * Registra una nueva cuenta bancaria o CBU/CVU receptora de cobros.
     */
    public function store(GuardarCuentaReceptoraRequest $request): RedirectResponse
    {
        CuentaReceptora::create($request->validated());

        return back()->with('exito', 'Cuenta receptora registrada.');
    }

    public function edit(CuentaReceptora $cuentaReceptora): View
    {
        return view('cuentas-receptoras.editar', ['cuenta' => $cuentaReceptora]);
    }

    /**
     * Actualiza los datos identificadores o estado de la cuenta receptora.
     */
    public function update(GuardarCuentaReceptoraRequest $request, CuentaReceptora $cuentaReceptora): RedirectResponse
    {
        $cuentaReceptora->update($request->validated());

        return redirect()->route('cuentas-receptoras.index')->with('exito', 'Cuenta receptora actualizada.');
    }

    public function cambiarEstado(Request $request, CuentaReceptora $cuentaReceptora): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::enum(EstadoCuentaReceptora::class)]]);
        if ($datos['estado'] === EstadoCuentaReceptora::Activa->value) {
            $cuentaReceptora->activar();

            return back()->with('exito', 'Cuenta receptora activada.');
        }

        $cuentaReceptora->desactivar();

        return back()->with('exito', 'Cuenta receptora desactivada.');
    }

    public function destroy(CuentaReceptora $cuentaReceptora): RedirectResponse
    {
        DB::transaction(function () use ($cuentaReceptora): void {
            $cuenta = CuentaReceptora::query()->lockForUpdate()->findOrFail($cuentaReceptora->getKey());
            if ($cuenta->pagos()->exists()) {
                throw ValidationException::withMessages([
                    'cuenta' => 'Esta cuenta tiene pagos registrados. Podés desactivarla para conservar el historial.',
                ]);
            }

            $cuenta->delete();
        }, 3);

        return back()->with('exito', 'Cuenta receptora eliminada.');
    }
}
