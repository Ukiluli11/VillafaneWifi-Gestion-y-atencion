<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\IniciarSesionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AutenticacionController: Controlador web para el inicio y cierre de sesion de operadores.
 * Corresponde a los casos de uso: 'Autenticar usuario' y 'Cerrar sesion' (Modulo 6).
 * Conecta con el modelo Usuario (metodo Usuario::autenticar()) y el facade Auth de Laravel.
 */
class AutenticacionController extends Controller
{
    /**
     * Muestra la vista del formulario de inicio de sesion.
     */
    public function create(): View
    {
        return view('autenticacion.iniciar-sesion');
    }

    /**
     * Valida credenciales, autentica al usuario en la plataforma y regenera la sesion activa.
     */
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

    /**
     * Cierra la sesion del usuario autenticado e invalida el token de sesion.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sesion.crear');
    }
}
