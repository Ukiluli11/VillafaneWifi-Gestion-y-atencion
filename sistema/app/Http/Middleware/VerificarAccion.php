<?php

namespace App\Http\Middleware;

use App\Autorizacion\ServicioAutorizacion;
use App\Enums\AccionSistema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAccion
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $accion): Response
    {
        $usuario = $request->user();
        $accionSistema = AccionSistema::tryFrom($accion);

        abort_unless(
            $usuario && $accionSistema && app(ServicioAutorizacion::class)->puede($usuario, $accionSistema),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
