<?php

namespace App\Http\Controllers\Web;

use App\Dominio\NotificacionService;
use App\Dominio\ServicioTickets;
use App\Enums\EstadoTicket;
use App\Enums\EstadoUsuario;
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
        $request->validate([
            'estado' => ['nullable', 'string', 'in:pendiente,asignado,en_proceso,resuelto,cerrado,reabierto'],
            'busqueda' => ['nullable', 'string', 'max:200'],
            'buscar' => ['nullable', 'string', 'max:200'],
            'tipo' => ['nullable', 'string', 'in:tecnico,administrativo,instalacion,reclamo'],
            'id_usuario_asignado' => ['nullable', 'regex:/^(sin_asignar|[0-9]+)$/'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('fecha_desde') ? ['after_or_equal:fecha_desde'] : [])],
        ]);
        $filtros = [
            'estado' => $request->query('estado'),
            'id_usuario_asignado' => $request->query('id_usuario_asignado'),
            'tipo' => $request->query('tipo'),
            'busqueda' => $request->query('busqueda', $request->query('buscar')),
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
        ];

        if ($request->boolean('mios')) {
            $filtros['id_usuario_asignado'] = $request->user()->id_usuario;
        }

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
            'total' => Ticket::count(),
            'pendientes' => Ticket::where('estado', EstadoTicket::Pendiente)->count(),
            'en_proceso' => Ticket::whereIn('estado', [EstadoTicket::Asignado, EstadoTicket::EnProceso])->count(),
            'reabiertos' => Ticket::where('estado', EstadoTicket::Reabierto)->count(),
            'resueltos' => Ticket::where('estado', EstadoTicket::Resuelto)->count(),
            'cerrados' => Ticket::where('estado', EstadoTicket::Cerrado)->count(),
        ];

        $tecnicos = Usuario::where('estado', EstadoUsuario::Activo->value)
            ->where(fn ($q) => $q->whereHas('administrador')->orWhereHas('empleado', fn ($e) => $e->where('area', 'soporte')))
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
        $idClienteSeleccionado = $request->query('id_cliente');

        $clientes = Cliente::with(['servicios' => function ($q) {
            $q->where('estado', '!=', 'baja');
        }])
            ->whereHas('servicios', function ($q) {
                $q->where('estado', '!=', 'baja');
            })
            ->orderBy('nombre_razon_social')
            ->get();

        $tecnicos = Usuario::where('estado', EstadoUsuario::Activo->value)
            ->where(fn ($q) => $q->whereHas('administrador')->orWhereHas('empleado', fn ($e) => $e->where('area', 'soporte')))
            ->orderBy('nombre_usuario')
            ->get();

        if (! $request->user()->administrador()->exists()) {
            $tecnicos = $tecnicos->where('id_usuario', $request->user()->id_usuario);
        }

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

        $tecnicos = Usuario::where('estado', EstadoUsuario::Activo->value)
            ->where(fn ($q) => $q->whereHas('administrador')->orWhereHas('empleado', fn ($e) => $e->where('area', 'soporte')))
            ->orderBy('nombre_usuario')
            ->get();

        $esAdministrador = auth()->user()->administrador()->exists();
        $puedeGestionar = $esAdministrador || $ticket->id_usuario_asignado === auth()->id();
        $puedeAsignar = ! in_array($ticket->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado], true)
            && ($esAdministrador || (auth()->user()->empleado()->where('area', 'soporte')->exists()
                && in_array($ticket->id_usuario_asignado, [null, auth()->id()], true)));
        if (! $esAdministrador) {
            $tecnicos = $tecnicos->where('id_usuario', auth()->id());
        }

        return view('tickets.detalle', compact('ticket', 'tecnicos', 'puedeGestionar', 'puedeAsignar'));
    }

    /**
     * Asigna formalmente el responsable técnico e inicia el seguimiento del ticket.
     */
    public function asignar(Request $request, Ticket $ticket): RedirectResponse
    {
        $campo = $request->has('id_usuario_asignado') ? 'id_usuario_asignado' : 'id_usuario';
        $request->validate([
            $campo => ['required', 'integer', 'exists:usuario,id_usuario'],
        ], [
            'id_usuario_asignado.required' => 'Debe seleccionar un técnico.',
            'id_usuario_asignado.exists' => 'El técnico seleccionado no existe.',
        ]);

        $tecnico = Usuario::findOrFail($request->input($campo));
        $this->servicioTickets->asignarTicket($ticket, $tecnico, $request->user(), $campo);

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Ticket asignado exitosamente al técnico {$tecnico->nombre_usuario}.");
    }

    /**
     * Controla las transiciones de ciclo de vida del ticket con notificación opcional por WhatsApp.
     */
    public function cambiarEstado(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'estado' => ['required', 'string'],
            'observacion' => ['nullable', 'string', 'max:2000'],
            'notificar_whatsapp' => ['nullable', 'boolean'],
        ]);

        $nuevoEstado = EstadoTicket::tryFrom($request->input('estado'));
        if (! $nuevoEstado) {
            return back()->withErrors(['estado' => 'El estado especificado no es válido.']);
        }

        $observacion = $request->input('observacion');
        $this->servicioTickets->cambiarEstado($ticket, $nuevoEstado, $request->user(), $observacion);

        // Notificación opcional al cliente por WhatsApp
        if ($request->boolean('notificar_whatsapp')) {
            $aviso = $this->notificacionService->notificarNovedad($ticket->fresh());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', "Estado del ticket actualizado a: {$nuevoEstado->value}.".(isset($aviso) ? ' '.$aviso['detalle'] : ''));
    }

    public function resolver(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->merge(['estado' => EstadoTicket::Resuelto->value]);

        return $this->cambiarEstado($request, $ticket);
    }

    public function cerrar(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->merge(['estado' => EstadoTicket::Cerrado->value]);

        return $this->cambiarEstado($request, $ticket);
    }

    /**
     * Mecanismo formal de reapertura de incidentes no solucionados.
     */
    public function reabrir(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'motivo' => ['nullable', 'string', 'min:5', 'max:2000'],
        ], [
            'motivo.required' => 'Debe ingresar el motivo de reapertura del ticket.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $this->servicioTickets->reabrirTicket($ticket, $request->user(), $request->input('motivo'));

        return redirect()->route('tickets.show', $ticket)
            ->with('exito', 'Ticket reabierto con éxito.');
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
