<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\IniciarSesionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AutenticacionController extends Controller
{
    public function create(): View
    {
        return view('autenticacion.iniciar-sesion');
    }

    public function store(IniciarSesionRequest $request): RedirectResponse
    {
        $credenciales = [
            'nombre_usuario' => $request->string('nombre_usuario')->toString(),
            'password' => $request->string('contrasena')->toString(),
            'estado' => 'activo',
        ];
        if (! Auth::attempt($credenciales)) {
            return back()->withErrors(['nombre_usuario' => 'Las credenciales no son válidas.'])->onlyInput('nombre_usuario');
        }
        $request->session()->regenerate();

        return redirect()->intended(route('inicio'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sesion.crear');
    }
}
