<?php

namespace App\Http\Middleware;

use App\Enums\EstadoUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controla que la cuenta autenticada siga habilitada para usar el sistema.
 */
class VerificarUsuarioActivo
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->estado !== EstadoUsuario::Activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'El usuario no está activo.');
            }

            return redirect()->route('sesion.crear')->withErrors([
                'nombre_usuario' => 'Tu usuario está desactivado. Consultá al administrador.',
            ]);
        }

        return $next($request);
    }
}
