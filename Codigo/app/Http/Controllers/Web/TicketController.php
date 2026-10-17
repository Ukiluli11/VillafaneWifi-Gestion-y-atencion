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
 * TicketController: Controlador web para la gestión de tickets y soporte técnico.
 * Corresponde a los casos de uso: 'Filtrar y listar tickets', 'Generar ticket de soporte',
 * 'Asignar ticket a técnico', 'Gestionar estado de ticket', 'Reabrir ticket', 'Agregar nota interna',
 * 'Visualizar conversación de chat asociada' y 'Notificar novedad por WhatsApp'.
 * Conecta con el modelo Ticket, ServicioTickets y NotificacionService.
 */
class TicketController extends Controller
{
    public function __construct(
        private readonly ServicioTickets $servicioTickets,
        private readonly NotificacionService $notificacionService,
    ) {}

    /**
     * Bandeja principal de tickets con filtrado avanzado multicriterio.
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
     * Presenta el formulario para registrar un ticket de soporte de manera manual.
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
     * Persiste el ticket manual en la base de datos con asignación opcional y nota inicial.
     */
    public function store(CrearTicketRequest $request): RedirectResponse
    {
        $ticket = $this->servicioTickets->crearManual($request->validated(), $request->user());

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Ticket #{$ticket->id_ticket} registrado correctamente.");
    }

    /**
     * Muestra datos del servicio, estado, línea de tiempo, notas y chat de WhatsApp vinculado.
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
     * Asigna formalmente el responsable técnico e inicia el seguimiento del ticket.
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
     * Controla las transiciones de ciclo de vida del ticket con notificación opcional por WhatsApp.
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

        // Notificación opcional al cliente por WhatsApp
        if ($request->boolean('notificar_whatsapp')) {
            $this->notificacionService->notificarNovedad($ticket, $observacion ?? '');
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Estado del ticket actualizado a: {$nuevoEstado->value}.");
    }

    /**
     * Mecanismo formal de reapertura de incidentes no solucionados.
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
     * Asienta anotaciones técnicas o comentarios internos en la bitácora del ticket.
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
