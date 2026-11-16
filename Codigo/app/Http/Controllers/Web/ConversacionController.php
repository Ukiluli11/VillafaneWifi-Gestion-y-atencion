<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioAtencionHumana;
use App\Dominio\ServicioConsumoWhatsapp;
use App\Enums\EstadoConversacion;
use App\Enums\ModoAtencion;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResponderConversacionRequest;
use App\Models\Conversacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * ConversacionController: Controlador web para la gestion de conversaciones y atencion escalada.
 * Corresponde a los casos de uso: 'Tomar control de conversacion escalada', 'Consultar historial de conversacion',
 * y 'Cerrar atencion escalada' (Modulo 2).
 * Conecta con Conversacion (metodos derivarAUsuario(), cerrar()), Empleado (atenderConversacion()) y ServicioAtencionHumana.
 */
class ConversacionController extends Controller
{
    /**
     * Lista las conversaciones activas, escaladas y cerradas con soporte de filtrado.
     */
    public function index(Request $request, ServicioConsumoWhatsapp $consumo): View
    {
        $estadoSeleccionado = EstadoConversacion::tryFrom($request->query->getString('estado'));
        $modo = ModoAtencion::tryFrom($request->query->getString('modo'));
        $asignacion = $request->query->getString('asignacion');
        $busqueda = trim($request->query->getString('buscar'));

        $consulta = Conversacion::query()
            ->with(['cliente', 'usuarioAtencion'])
            ->withCount('mensajes')
            ->withMax('mensajes', 'fecha_hora')
            ->when($estadoSeleccionado, fn ($consulta) => $consulta->where('estado', $estadoSeleccionado->value))
            ->when($modo, fn ($consulta) => $consulta->where('modo_atencion', $modo->value))
            ->when($asignacion === 'sin_asignar', fn ($consulta) => $consulta->whereNull('id_usuario_atencion'))
            ->when($asignacion === 'propias', fn ($consulta) => $consulta->where('id_usuario_atencion', $request->user()->getKey()));

        if ($busqueda !== '') {
            $numero = preg_replace('/\D+/', '', $busqueda) ?? '';
            $idBuscado = filter_var(ltrim($busqueda, '#'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $consulta->where(function ($filtro) use ($busqueda, $numero, $idBuscado): void {
                $filtro->whereHas('cliente', fn ($cliente) => $cliente->whereLike('nombre_razon_social', "%{$busqueda}%"));
                if ($numero !== '') {
                    $filtro->orWhereLike('numero_whatsapp', "%{$numero}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->whereLike('numero_documento', "%{$numero}%"));
                }
                if ($idBuscado !== false) {
                    $filtro->orWhere('id_conversacion', $idBuscado);
                }
            });
        }

        return view('conversaciones.index', [
            'conversaciones' => $consulta
                ->orderByRaw("CASE WHEN estado = 'escalada' THEN 0 WHEN estado = 'en_atencion' THEN 1 ELSE 2 END")
                ->orderByDesc('mensajes_max_fecha_hora')
                ->orderByDesc('id_conversacion')
                ->paginate(20)
                ->withQueryString(),
            'estadoSeleccionado' => $estadoSeleccionado,
            'modo' => $modo,
            'asignacion' => $asignacion,
            'busqueda' => $busqueda,
            'estados' => EstadoConversacion::cases(),
            'consumoWhatsapp' => $consumo->resumir(),
        ]);
    }

    /**
     * Muestra la bandeja y el historial completo de mensajes intercambiados en la conversacion.
     */
    public function show(Conversacion $conversacion): View
    {
        $conversacion->load([
            'cliente',
            'usuarioAtencion',
            'mensajes' => fn ($consulta) => $consulta->with('usuario')->orderBy('fecha_hora')->orderBy('id_mensaje'),
            'tickets.servicio',
            'comprobantes',
        ]);

        return view('conversaciones.detalle', ['conversacion' => $conversacion]);
    }

    /**
     * Toma el control de una conversacion escalada para atencion humana directa por el empleado.
     */
    public function tomar(
        Request $request,
        Conversacion $conversacion,
        ServicioAtencionHumana $atencion,
    ): RedirectResponse {
        try {
            $atencion->tomar($conversacion, $request->user());
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);

            return back()->withErrors(['conversacion' => 'La conversación quedó asignada, pero no se pudo enviar la presentación por WhatsApp.']);
        }

        return back()->with('exito', 'Tomaste la conversación y se envió tu presentación al cliente.');
    }

    /**
     * Despacha un mensaje de respuesta escrito por el operador humano hacia el WhatsApp del cliente.
     */
    public function responder(
        ResponderConversacionRequest $request,
        Conversacion $conversacion,
        ServicioAtencionHumana $atencion,
    ): RedirectResponse {
        try {
            $atencion->responder($conversacion, $request->user(), $request->validated('contenido'));
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);

            return back()->withErrors(['contenido' => 'No se pudo enviar el mensaje por WhatsApp. Quedó registrado como fallido.']);
        }

        return back()->with('exito', 'Mensaje enviado y guardado en el historial.');
    }

    /**
     * Finaliza la atencion escalada y cierra la conversacion en el sistema.
     */
    public function cerrar(
        Request $request,
        Conversacion $conversacion,
        ServicioAtencionHumana $atencion,
    ): RedirectResponse {
        $atencion->cerrar($conversacion, $request->user());

        return redirect()->route('conversaciones.index')->with('exito', 'Conversación cerrada correctamente.');
    }
}
