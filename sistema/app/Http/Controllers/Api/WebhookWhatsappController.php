<?php

namespace App\Http\Controllers\Api;

use App\Dominio\ServicioRecepcionWhatsapp;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Expone el punto de entrada que Meta utiliza para verificar y notificar eventos.
 */
class WebhookWhatsappController extends Controller
{
    /**
     * Responde el desafío que confirma que la URL pertenece al sistema.
     */
    public function verificar(Request $request, ServicioRecepcionWhatsapp $servicio): Response
    {
        $modo = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $desafio = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        if (! $servicio->verificarSuscripcion($modo, $token) || ! is_string($desafio)) {
            return response('Verificación rechazada.', Response::HTTP_FORBIDDEN);
        }

        return response($desafio, Response::HTTP_OK)->header('Content-Type', 'text/plain');
    }

    /**
     * Valida la firma y procesa el contenido entregado por WhatsApp.
     */
    public function recibir(Request $request, ServicioRecepcionWhatsapp $servicio): JsonResponse
    {
        if (! $servicio->verificarFirma($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response()->json(['recibido' => false], Response::HTTP_UNAUTHORIZED);
        }

        $resultado = $servicio->procesar($request->json()->all());

        return response()->json(['recibido' => true] + $resultado);
    }
}
