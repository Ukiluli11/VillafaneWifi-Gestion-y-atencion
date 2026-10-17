<?php

namespace App\Http\Controllers\Web;

use App\Dominio\NotificacionService;
use App\Dominio\ServicioTickets;
use App\Enums\EstadoTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgregarNotaInternaRequest;
use App\Http\Requests\CrearTicketRequest;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TicketController: Controlador de soporte técnico y gestión de tickets (Módulo 4).
 * Responsable de la bandeja web, filtros, generación manual, asignación a técnicos,
 * transiciones de estado, notas internas y trazabilidad con WhatsApp.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly ServicioTickets $servicioTickets,
        private readonly NotificacionService $notificacionService,
    ) {}

    /**
     * Bandeja principal de tickets con filtrado avanzado (RF-28).
     */
    public function index(Request $request): View
    {
        $filtros = [
            'estado'              => $request->query('estado'),
            'id_usuario_asignado' => $request->query('id_usuario_asignado'),
            'tipo'                => $request->query('tipo'),
            'busqueda'            => $request->query('busqueda'),
            'fecha_desde'         => $request->query('fecha_desde'),
            'fecha_hasta'         => $request->query('fecha_hasta'),
        ];

        $tickets = Ticket::with(['servicio.cliente', 'servicio.plan', 'usuarioAsignado'])
            ->filtrar($filtros)
            ->orderByRaw("
                CASE estado
                    WHEN 'pendiente' THEN 1
                    WHEN 'reabierto' THEN 2
                    WHEN 'en_proceso' THEN 3
                    WHEN 'asignado' THEN 4
                    WHEN 'resuelto' THEN 5
                    WHEN 'cerrado' THEN 6
                    ELSE 7
                END
            ")
            ->orderByDesc('id_ticket')
            ->paginate(15)
            ->withQueryString();

        $estadisticas = [
            'total'       => Ticket::count(),
            'pendientes'  => Ticket::where('estado', EstadoTicket::Pendiente)->count(),
            'en_proceso'  => Ticket::whereIn('estado', [EstadoTicket::Asignado, EstadoTicket::EnProceso])->count(),
            'reabiertos'  => Ticket::where('estado', EstadoTicket::Reabierto)->count(),
            'resueltos'   => Ticket::where('estado', EstadoTicket::Resuelto)->count(),
            'cerrados'    => Ticket::where('estado', EstadoTicket::Cerrado)->count(),
        ];

        $tecnicos = Usuario::where('estado', 'activo')
            ->orderBy('nombre_usuario')
            ->get();

        return view('tickets.index', compact('tickets', 'filtros', 'estadisticas', 'tecnicos'));
    }

    /**
     * Pantalla para registrar un ticket de soporte de manera manual (RF-27).
     */
    public function create(Request $request): View
    {
        $idServicioSeleccionado = $request->query('id_servicio');
        $idClienteSeleccionado  = $request->query('id_cliente');

        $clientes = Cliente::with(['servicios' => function ($q) {
                $q->where('estado', '!=', 'baja');
            }])
            ->whereHas('servicios', function ($q) {
                $q->where('estado', '!=', 'baja');
            })
            ->orderBy('nombre_razon_social')
            ->get();

        $tecnicos = Usuario::where('estado', 'activo')
            ->orderBy('nombre_usuario')
            ->get();

        return view('tickets.formulario', compact('clientes', 'tecnicos', 'idServicioSeleccionado', 'idClienteSeleccionado'));
    }

    /**
     * Guarda el ticket manual en la base de datos (RF-27).
     */
    public function store(CrearTicketRequest $request): RedirectResponse
    {
        $ticket = $this->servicioTickets->crearManual($request->validated(), $request->user());

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Ticket #{$ticket->id_ticket} registrado correctamente.");
    }

    /**
     * Detalle completo del ticket: datos, estado, notas internas y chat asociado (RF-28, RF-32, RF-33).
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'servicio.cliente',
            'servicio.plan',
            'usuarioAsignado',
            'conversacion.mensajes.usuario',
            'notas.usuario',
        ]);

        $tecnicos = Usuario::where('estado', 'activo')
            ->orderBy('nombre_usuario')
            ->get();

        return view('tickets.detalle', compact('ticket', 'tecnicos'));
    }

    /**
     * Asignar ticket a un técnico responsable (RF-29).
     */
    public function asignar(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'id_usuario_asignado' => ['required', 'integer', 'exists:usuario,id_usuario'],
        ], [
            'id_usuario_asignado.required' => 'Debe seleccionar un técnico.',
            'id_usuario_asignado.exists'   => 'El técnico seleccionado no existe.',
        ]);

        $tecnico = Usuario::findOrFail($request->input('id_usuario_asignado'));
        $this->servicioTickets->asignarTicket($ticket, $tecnico);

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Ticket asignado exitosamente al técnico {$tecnico->nombre_usuario}.");
    }

    /**
     * Gestión de transiciones de estado: en proceso, resuelto, cerrado (RF-30).
     */
    public function cambiarEstado(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'estado'             => ['required', 'string'],
            'observacion'        => ['nullable', 'string', 'max:2000'],
            'notificar_whatsapp' => ['nullable', 'boolean'],
        ]);

        $nuevoEstado = EstadoTicket::tryFrom($request->input('estado'));
        if (!$nuevoEstado) {
            return back()->withErrors(['estado' => 'El estado especificado no es válido.']);
        }

        $observacion = $request->input('observacion');
        $this->servicioTickets->cambiarEstado($ticket, $nuevoEstado, $request->user(), $observacion);

        // Notificación opcional al cliente por WhatsApp (RF-33)
        if ($request->boolean('notificar_whatsapp')) {
            $this->notificacionService->notificarNovedad($ticket, $observacion ?? '');
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Estado del ticket actualizado a: {$nuevoEstado->value}.");
    }

    /**
     * Mecanismo formal de reapertura de ticket ante reclamo persistente (RF-31).
     */
    public function reabrir(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motivo.required' => 'Debe ingresar el motivo de reapertura del ticket.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $this->servicioTickets->reabrirTicket($ticket, $request->user(), $request->input('motivo'));

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Ticket reabierto con éxito.");
    }

    /**
     * Agregar nota técnica o comentario interno a la bitácora del ticket (RF-32).
     */
    public function agregarNota(AgregarNotaInternaRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->servicioTickets->agregarNotaInterna(
            $ticket,
            $request->user(),
            $request->validated()['contenido']
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', 'Nota interna agregada correctamente.');
    }
}
