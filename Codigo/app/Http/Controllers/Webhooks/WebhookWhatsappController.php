<?php

namespace App\Http\Controllers\Webhooks;

use App\Dominio\ServicioAtencionWhatsapp;
use App\Dominio\ServicioConversaciones;
use App\Dominio\ServicioIdentificacion;
use App\Dominio\WhatsAppService;
use App\Enums\EstadoEnvioMensaje;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookWhatsappController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly ServicioConversaciones $servicioConversaciones,
        private readonly ServicioIdentificacion $servicioIdentificacion,
        private readonly ServicioAtencionWhatsapp $servicioAtencionWhatsapp,
    ) {}

    public function verificar(Request $request): Response
    {
        $reto = $this->whatsAppService->verificarSuscripcion(
            $request->query('hub_mode', $request->query('hub.mode')),
            $request->query('hub_verify_token', $request->query('hub.verify_token')),
            $request->query('hub_challenge', $request->query('hub.challenge')),
        );

        abort_if($reto === null, 403, 'No se pudo verificar la suscripción de WhatsApp.');

        return response($reto, 200)->header('Content-Type', 'text/plain');
    }

    public function recibir(Request $request): JsonResponse
    {
        if (! $this->whatsAppService->validarFirma(
            $request->getContent(),
            $request->header('X-Hub-Signature-256'),
        )) {
            return response()->json(['mensaje' => 'Firma de webhook inválida.'], 401);
        }

        $payload = $request->json()->all();
        foreach ($this->whatsAppService->extraerEstados($payload) as $actualizacion) {
            $estado = EstadoEnvioMensaje::tryFrom($actualizacion['estado']);
            if ($estado !== null) {
                $this->servicioConversaciones->actualizarEstadoExterno($actualizacion['id_externo'], $estado);
            }
        }

        foreach ($this->whatsAppService->extraerMensajes($payload) as $evento) {
            $conversacion = $this->servicioConversaciones->obtenerOCrearConversacion($evento['telefono']);
            $mensaje = $this->servicioConversaciones->registrarMensajeEntrante($conversacion, $evento);

            if (! $mensaje->wasRecentlyCreated) {
                continue;
            }

            $cliente = $this->servicioIdentificacion->identificarCliente(
                $conversacion,
                $evento['telefono'],
                $evento['contenido'],
            );

            $respuesta = $this->servicioAtencionWhatsapp->procesarMensaje(
                $conversacion,
                $mensaje,
                $cliente,
            );

            if ($respuesta === null) {
                continue;
            }

            try {
                $idExterno = $this->whatsAppService->enviarMensaje($evento['telefono'], $respuesta);
                $this->servicioConversaciones->registrarMensajeSaliente(
                    $conversacion,
                    $respuesta,
                    $idExterno,
                    EstadoEnvioMensaje::Enviado,
                );
            } catch (Throwable $error) {
                $this->servicioConversaciones->registrarMensajeSaliente(
                    $conversacion,
                    $respuesta,
                    null,
                    EstadoEnvioMensaje::Fallido,
                );

                Log::error('No se pudo responder el mensaje recibido por WhatsApp.', [
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_mensaje' => $mensaje->id_mensaje,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        return response()->json(['recibido' => true]);
    }
}
