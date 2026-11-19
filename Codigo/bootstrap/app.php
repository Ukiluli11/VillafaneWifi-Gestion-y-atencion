<?php

// Configura el arranque de Laravel, las rutas, los middleware y el tratamiento de excepciones.

use App\Http\Middleware\VerificarAccion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['contrasena', 'contrasena_confirmation', 'contrasena_actual', 'contrasena_nueva', 'contrasena_nueva_confirmation']);
        $middleware->alias(['accion' => VerificarAccion::class]);
        $middleware->preventRequestForgery(except: ['webhooks/whatsapp']);

        $middleware->redirectGuestsTo(fn (): string => route('sesion.crear'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['contrasena', 'contrasena_confirmation', 'contrasena_actual', 'contrasena_nueva', 'contrasena_nueva_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
