<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoTicket;
use App\Enums\EstadoUsuario;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->string('estado')->toString();
        $busqueda = trim($request->string('buscar')->toString());
        $mios = $request->boolean('mios');
        $consulta = Ticket::query()->with(['servicio.cliente', 'usuarioAsignado']);

        if (EstadoTicket::tryFrom($estado) !== null) {
            $consulta->where('estado', $estado);
        }
        if ($mios) {
            $consulta->where('id_usuario_asignado', $request->user()->id_usuario);
        }
        if ($busqueda !== '') {
            $consulta->where(function ($filtro) use ($busqueda): void {
                if (ctype_digit($busqueda)) {
                    $filtro->where('id_ticket', (int) $busqueda);
                }
                $filtro->orWhereHas('servicio.cliente', fn ($cliente) => $cliente
                    ->where('nombre_razon_social', 'like', "%{$busqueda}%"));
            });
        }

        return view('tickets.index', [
            'tickets' => $consulta->orderByDesc('id_ticket')->paginate(20)->withQueryString(),
            'estados' => EstadoTicket::cases(),
            'estadoSeleccionado' => $estado,
            'busqueda' => $busqueda,
            'mios' => $mios,
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'conversacion',
            'servicio.cliente',
            'servicio.plan',
            'usuarioAsignado',
            'notas' => fn ($consulta) => $consulta->with('usuario')->orderBy('fecha_hora')->orderBy('id_nota'),
        ]);

        $responsables = Usuario::query()
            ->where('estado', EstadoUsuario::Activo->value)
            ->where(function ($consulta): void {
                $consulta->whereHas('administrador')
                    ->orWhereHas('empleado', fn ($empleado) => $empleado->where('area', 'soporte'));
            })
            ->orderBy('nombre_usuario')
            ->get();

        $usuario = auth()->user();
        $esAdministrador = $usuario->administrador()->exists();

        return view('tickets.detalle', [
            'ticket' => $ticket,
            'responsables' => $responsables,
            'puedeGestionar' => $this->puedeGestionar($ticket, $usuario),
            'esAdministrador' => $esAdministrador,
            'puedeAsignar' => $esAdministrador || ($usuario->empleado()->where('area', 'soporte')->exists()
                && in_array($ticket->id_usuario_asignado, [null, $usuario->id_usuario], true)),
        ]);
    }

    public function asignar(Request $request, Ticket $ticket): RedirectResponse
    {
        $datos = $request->validate(['id_usuario' => ['required', 'integer', 'exists:usuario,id_usuario']]);
        $responsable = Usuario::query()
            ->whereKey($datos['id_usuario'])
            ->where('estado', EstadoUsuario::Activo->value)
            ->where(function ($consulta): void {
                $consulta->whereHas('administrador')
                    ->orWhereHas('empleado', fn ($empleado) => $empleado->where('area', 'soporte'));
            })
            ->first();

        $esAdministrador = $request->user()->administrador()->exists();
        if ($responsable === null || (! $esAdministrador && (
            $responsable->id_usuario !== $request->user()->id_usuario
            || ! in_array($ticket->id_usuario_asignado, [null, $request->user()->id_usuario], true)
        ))) {
            throw ValidationException::withMessages(['id_usuario' => 'Elegí un responsable de soporte habilitado.']);
        }
        if (in_array($ticket->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado], true)) {
            throw ValidationException::withMessages(['ticket' => 'Reabrí el ticket antes de asignarlo.']);
        }

        $ticket->asignar($responsable);

        return back()->with('exito', 'Ticket asignado a '.$responsable->nombre_usuario.'.');
    }

    public function agregarNota(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->puedeGestionar($ticket, $request->user()), 403);
        $datos = $request->validate(['contenido' => ['required', 'string', 'min:3', 'max:5000']]);
        $ticket->agregarNota(trim($datos['contenido']), $request->user());

        return back()->with('exito', 'Nota interna guardada.');
    }

    public function resolver(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->puedeGestionar($ticket, $request->user()), 403);
        if (! in_array($ticket->estado, [EstadoTicket::Asignado, EstadoTicket::EnProceso, EstadoTicket::Reabierto], true)) {
            throw ValidationException::withMessages(['ticket' => 'Asigná o reabrí el ticket antes de resolverlo.']);
        }

        $ticket->resolver();

        return back()->with('exito', 'Ticket marcado como resuelto.');
    }

    public function cerrar(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->puedeGestionar($ticket, $request->user()), 403);
        if ($ticket->estado !== EstadoTicket::Resuelto) {
            throw ValidationException::withMessages(['ticket' => 'El ticket debe estar resuelto antes de cerrarlo.']);
        }

        $ticket->cerrar();

        return back()->with('exito', 'Ticket cerrado.');
    }

    public function reabrir(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($this->puedeGestionar($ticket, $request->user()), 403);
        if (! in_array($ticket->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado], true)) {
            throw ValidationException::withMessages(['ticket' => 'Solo se puede reabrir un ticket resuelto o cerrado.']);
        }

        $ticket->reabrir();

        return back()->with('exito', 'Ticket reabierto.');
    }

    private function puedeGestionar(Ticket $ticket, Usuario $usuario): bool
    {
        return $usuario->administrador()->exists()
            || $ticket->id_usuario_asignado === $usuario->id_usuario;
    }
}
