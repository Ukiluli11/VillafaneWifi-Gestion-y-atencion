<?php

namespace App\Http\Controllers\Web;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            ->orderBy('nombre')
            ->get();

        $mediosPago = MedioPago::cases();

        return view('comprobantes.detalle', compact('comprobante', 'cuotasImpagas', 'cuentasReceptoras', 'mediosPago'));
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
            $request->user()
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
            ->with('exito', 'Comprobante rechazado. Se registró el motivo y se generó la notificación.');
    }
}
