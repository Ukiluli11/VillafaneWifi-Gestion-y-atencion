<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ServicioAtencionHumana;
use App\Enums\EstadoConversacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResponderConversacionRequest;
use App\Models\Conversacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ConversacionController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->string('estado')->toString();
        $busqueda = trim($request->string('buscar')->toString());
        $permitidos = array_map(
            static fn (EstadoConversacion $item): string => $item->value,
            EstadoConversacion::cases(),
        );

        $consulta = Conversacion::query()
            ->with(['cliente', 'usuarioAtencion'])
            ->withCount('mensajes')
            ->withMax('mensajes', 'fecha_hora');

        if (in_array($estado, $permitidos, true)) {
            $consulta->where('estado', $estado);
        }

        if ($busqueda !== '') {
            $numero = preg_replace('/\D+/', '', $busqueda) ?? '';
            $consulta->where(function ($filtro) use ($busqueda, $numero): void {
                $filtro->whereHas('cliente', fn ($cliente) => $cliente
                    ->where('nombre_razon_social', 'like', "%{$busqueda}%")
                    ->orWhere('numero_documento', 'like', "%{$numero}%"));
                if ($numero !== '') {
                    $filtro->orWhere('numero_whatsapp', 'like', "%{$numero}%");
                }
            });
        }

        return view('conversaciones.index', [
            'conversaciones' => $consulta
                ->orderByRaw("CASE WHEN estado = 'escalada' THEN 0 WHEN estado = 'en_atencion' THEN 1 ELSE 2 END")
                ->orderByDesc('mensajes_max_fecha_hora')
                ->paginate(20)
                ->withQueryString(),
            'estadoSeleccionado' => $estado,
            'busqueda' => $busqueda,
            'estados' => EstadoConversacion::cases(),
        ]);
    }

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

    public function cerrar(
        Request $request,
        Conversacion $conversacion,
        ServicioAtencionHumana $atencion,
    ): RedirectResponse {
        $atencion->cerrar($conversacion, $request->user());

        return redirect()->route('conversaciones.index')->with('exito', 'Conversación cerrada correctamente.');
    }
}
