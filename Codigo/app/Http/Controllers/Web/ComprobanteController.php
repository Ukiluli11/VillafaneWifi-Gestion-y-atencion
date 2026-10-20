<?php

namespace App\Http\Controllers\Web;

use App\Dominio\NotificacionService;
use App\Dominio\OCRService;
use App\Dominio\ServicioComprobantes;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoCuota;
use App\Enums\MedioPago;
use App\Http\Controllers\Controller;
use App\Http\Requests\AprobarComprobanteRequest;
use App\Http\Requests\RechazarComprobanteRequest;
use App\Http\Requests\SubirComprobanteRequest;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ComprobanteController: Controlador web para la bandeja de conciliacion y dictamen de comprobantes.
 * Corresponde a los casos de uso: 'Visualizar bandeja de conciliacion', 'Validar comprobante de pago',
 * 'Aprobar comprobante' y 'Rechazar comprobante' (Modulo 3).
 * Conecta con Comprobante (metodos aprobar(), rechazar(), esDuplicado()), Pago (imputar()) y ServicioComprobantes.
 */
class ComprobanteController extends Controller
{
    public function __construct(
        private readonly ServicioComprobantes $servicioComprobantes
    ) {}

    /**
     * Muestra la bandeja de comprobantes con filtros por estado y buscador.
     */
    public function index(Request $request): View
    {
        $estadoFiltro = $request->query('estado');
        $busqueda = $request->string('buscar')->trim()->toString();

        $query = Comprobante::with(['cliente', 'pago']);

        if ($estadoFiltro && in_array($estadoFiltro, ['pendiente', 'aprobado', 'rechazado'], true)) {
            $query->where('estado_validacion', $estadoFiltro);
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero_operacion', 'like', "%{$busqueda}%")
                    ->orWhere('hash_archivo', 'like', "%{$busqueda}%")
                    ->orWhereHas('cliente', function ($qc) use ($busqueda) {
                        $qc->where('nombre_razon_social', 'like', "%{$busqueda}%")
                            ->orWhere('numero_documento', 'like', "%{$busqueda}%")
                            ->orWhere('telefono_whatsapp', 'like', "%{$busqueda}%");
                    });
            });
        }

        $comprobantes = $query->latest('id_comprobante')->paginate(15)->withQueryString();

        $totalPendientes = Comprobante::pendientes()->count();
        $totalAprobados = Comprobante::aprobados()->count();
        $totalRechazados = Comprobante::rechazados()->count();

        return view('comprobantes.index', compact(
            'comprobantes',
            'estadoFiltro',
            'busqueda',
            'totalPendientes',
            'totalAprobados',
            'totalRechazados'
        ));
    }

    /**
     * Muestra el formulario para recepcion manual de comprobantes de pago.
     */
    public function create(): View
    {
        $clientes = Cliente::query()
            ->where('estado', '!=', 'baja')
            ->orderBy('nombre_razon_social')
            ->get();

        return view('comprobantes.formulario', compact('clientes'));
    }

    public function archivo(Comprobante $comprobante): StreamedResponse
    {
        $ruta = $comprobante->ruta_archivo;
        abort_unless(is_string($ruta) && str_starts_with($ruta, 'comprobantes/') && ! str_contains($ruta, '..'), 404);
        $disco = Storage::disk('local');
        abort_unless($disco->exists($ruta), 404, 'El archivo no está disponible en el almacenamiento privado.');

        return $disco->download($ruta, basename($ruta), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reprocesarOcr(Comprobante $comprobante, OCRService $ocr): RedirectResponse
    {
        abort_unless($comprobante->estaPendiente(), 409);
        if (! $ocr->estaConfigurado()) {
            return back()->withErrors(['comprobante' => 'La lectura automática todavía no está configurada. Podés revisar y completar los datos manualmente.']);
        }
        $reclamado = Comprobante::whereKey($comprobante->id_comprobante)
            ->where('estado_validacion', EstadoComprobante::Pendiente->value)
            ->whereNotIn('estado_ocr', ['en_cola', 'procesando'])
            ->update(['estado_ocr' => 'en_cola', 'error_ocr' => null]);
        if (! $reclamado) {
            return back()->withErrors(['comprobante' => 'La lectura ya está en curso o el comprobante ya fue evaluado.']);
        }
        $ocr->encolarLectura($comprobante);
        if ($comprobante->fresh()->estado_ocr === 'fallido') {
            return back()->withErrors(['comprobante' => 'El archivo está guardado, pero no se pudo programar la lectura. Intentá nuevamente.']);
        }

        return back()->with('exito', 'La lectura del comprobante quedó en cola.');
    }

    public function notificar(Comprobante $comprobante, NotificacionService $notificaciones): RedirectResponse
    {
        abort_if($comprobante->estaPendiente(), 409);
        $resultado = $comprobante->estaAprobado()
            ? $notificaciones->notificarPagoAprobado($comprobante->cliente, $comprobante->pago, $comprobante)
            : $notificaciones->notificarPagoRechazado($comprobante->cliente, $comprobante, $comprobante->motivo_rechazo);

        return back()->with('exito', $resultado['detalle']);
    }

    /**
     * Registra un comprobante subido manualmente calculando su hash y ejecutando el OCR.
     */
    public function store(SubirComprobanteRequest $request): RedirectResponse
    {
        $cliente = Cliente::findOrFail($request->validated('id_cliente'));

        $comprobante = $this->servicioComprobantes->registrarComprobante(
            $cliente,
            $request->file('archivo'),
            $request->validated('numero_operacion'),
            $request->validated('monto'),
            $request->validated('fecha')
        );

        return redirect()
            ->route('comprobantes.show', $comprobante)
            ->with('exito', 'Comprobante recibido y registrado para conciliación.');
    }

    /**
     * Muestra el detalle del comprobante para auditoria humana, exhibiendo cuotas impagas y datos OCR.
     */
    public function show(Comprobante $comprobante): View
    {
        $comprobante->load(['cliente.servicios.plan', 'pago.cuenta', 'pago.cuotas', 'usuario']);

        // Cuotas impagas del cliente (pendientes y vencidas) para análisis en la conciliación
        $cuotasImpagas = Cuota::query()
            ->whereHas('servicio', fn ($q) => $q->where('id_cliente', $comprobante->id_cliente))
            ->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->orderBy('fecha_vencimiento', 'asc')
            ->with('servicio.plan')
            ->get();

        $cuentasReceptoras = CuentaReceptora::query()
            ->where('estado', 'activa')
            ->when(config('services.transferencias.habilitadas'), fn ($q) => $q->whereKey(config('services.transferencias.id_cuenta')))
            ->orderBy('nombre')
            ->get();

        $mediosPago = MedioPago::cases();
        $tipoAviso = $comprobante->estaAprobado() ? 'aprobado' : 'rechazado';
        $avisoResultado = $comprobante->estaPendiente() ? null : Mensaje::query()
            ->where('clave_idempotencia', "comprobante:{$tipoAviso}:{$comprobante->id_comprobante}")->first();

        return view('comprobantes.detalle', compact('comprobante', 'cuotasImpagas', 'cuentasReceptoras', 'mediosPago', 'avisoResultado'));
    }

    /**
     * Dictamina la aprobacion del comprobante, genera el registro de Pago e imputa las cuotas adeudadas.
     */
    public function aprobar(AprobarComprobanteRequest $request, Comprobante $comprobante): RedirectResponse
    {
        $cuenta = CuentaReceptora::findOrFail($request->validated('id_cuenta'));
        $medio = MedioPago::from($request->validated('medio_pago'));

        $pago = $this->servicioComprobantes->conciliarYAprobar(
            $comprobante,
            $cuenta,
            $medio,
            $request->validated('fecha'),
            $request->validated('monto'),
            $request->user(),
            $request->validated('numero_operacion'),
        );

        return redirect()
            ->route('comprobantes.show', $comprobante)
            ->with('exito', "¡Comprobante aprobado! Se registró el Pago #{$pago->id_pago} por \${$pago->monto_total} y se imputaron las cuotas.");
    }

    /**
     * Dictamina el rechazo del comprobante registrando el motivo justificado y notificando al cliente.
     */
    public function rechazar(RechazarComprobanteRequest $request, Comprobante $comprobante): RedirectResponse
    {
        $this->servicioComprobantes->rechazarComprobante(
            $comprobante,
            $request->validated('motivo_rechazo'),
            $request->user()
        );

        return redirect()
            ->route('comprobantes.show', $comprobante)
            ->with('exito', 'Comprobante rechazado. Se registró el motivo; el envío del aviso se consulta por separado.');
    }
}
