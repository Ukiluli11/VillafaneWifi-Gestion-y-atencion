<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioAtencionHumanaWhatsapp;
use App\Dominio\ServicioConsultaConversaciones;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResponderConversacionRequest;
use App\Models\Conversacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Presenta las conversaciones y sus mensajes en el panel administrativo.
 */
class ConversacionController extends Controller
{
    /**
     * Muestra el listado paginado con búsqueda y filtro por estado.
     */
    public function index(Request $request, ServicioConsultaConversaciones $servicio): View
    {
        return view('conversaciones.index', [
            'conversaciones' => $servicio->buscar(
                $request->string('buscar')->toString(),
                $request->string('estado')->toString(),
            ),
        ]);
    }

    /**
     * Muestra el historial cronológico de una conversación.
     */
    public function show(Conversacion $conversacion, ServicioConsultaConversaciones $servicio): View
    {
        return view('conversaciones.detalle', [
            'conversacion' => $servicio->cargarDetalle($conversacion),
        ]);
    }

    /**
     * Asigna la conversación al usuario autenticado y lo presenta en el chat.
     */
    public function tomar(
        Conversacion $conversacion,
        Request $request,
        ServicioAtencionHumanaWhatsapp $servicio,
    ): RedirectResponse {
        try {
            $resultado = $servicio->tomar($conversacion, $request->user());
        } catch (ConnectionException|RequestException $error) {
            report($error);

            return redirect()
                ->route('conversaciones.show', $conversacion)
                ->withErrors([
                    'whatsapp' => 'La conversación quedó asignada, pero no se pudo enviar la presentación por WhatsApp. Revisá que el destinatario esté autorizado y que la conexión con Meta esté disponible.',
                ]);
        }

        $mensaje = $resultado->nuevaAsignacion
            ? 'Tomaste la conversación y el cliente recibió tu presentación.'
            : 'La conversación ya estaba asignada a tu usuario.';

        return redirect()
            ->route('conversaciones.show', $conversacion)
            ->with('exito', $mensaje);
    }

    /**
     * Envía una respuesta humana y la incorpora al historial compartido.
     */
    public function responder(
        Conversacion $conversacion,
        ResponderConversacionRequest $request,
        ServicioAtencionHumanaWhatsapp $servicio,
    ): RedirectResponse {
        try {
            $servicio->responder(
                $conversacion,
                $request->user(),
                $request->string('contenido')->toString(),
            );
        } catch (ConnectionException|RequestException $error) {
            report($error);

            return redirect()
                ->route('conversaciones.show', $conversacion)
                ->withInput()
                ->withErrors([
                    'whatsapp' => 'No se pudo entregar la respuesta por WhatsApp. El intento quedó registrado como fallido para su revisión.',
                ]);
        }

        return redirect()
            ->route('conversaciones.show', $conversacion)
            ->with('exito', 'La respuesta fue enviada al cliente.');
    }

    /**
     * Cierra una conversación y conserva su historial para futuras consultas.
     */
    public function cerrar(
        Conversacion $conversacion,
        Request $request,
        ServicioAtencionHumanaWhatsapp $servicio,
    ): RedirectResponse {
        try {
            $servicio->cerrar($conversacion, $request->user());
        } catch (ConnectionException|RequestException $error) {
            report($error);

            return redirect()
                ->route('conversaciones.show', $conversacion)
                ->withErrors([
                    'whatsapp' => 'No se pudo avisar el cierre por WhatsApp. La conversación permanece abierta para evitar perder la atención.',
                ]);
        }

        return redirect()
            ->route('conversaciones.show', $conversacion)
            ->with('exito', 'La conversación fue cerrada correctamente.');
    }
}
