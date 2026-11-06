<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ExportService;
use App\Dominio\ReporteService;
use App\Dominio\ServicioNotificacionesVencimiento;
use App\Enums\EstadoTicket;
use App\Enums\MedioPago;
use App\Http\Controllers\Controller;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Plan;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reporteService) {}

    /**
     * Dashboard Ejecutivo con KPIs interactivos y gráficos (RF-37, RF-38).
     */
    public function dashboard(): View
    {
        $metricas = $this->reporteService->obtenerMetricasDashboard();

        return view('reportes.dashboard', compact('metricas'));
    }

    /**
     * Reporte de clientes al día y en mora (RF-34).
     */
    public function morosidad(Request $request): View
    {
        $filtros = $this->validarFiltros($request, 'morosidad');

        $reporte = $this->reporteService->obtenerReporteMorosidad($filtros);
        $planes = Plan::orderBy('nombre')->get();

        return view('reportes.morosidad', [
            'items' => $reporte['items'],
            'resumen' => $reporte['resumen'],
            'filtros' => $filtros,
            'planes' => $planes,
        ]);
    }

    /**
     * Reporte financiero de ingresos por período (RF-35).
     */
    public function ingresos(Request $request): View
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $filtros = $this->validarFiltros($request, 'ingresos');

        $reporte = $this->reporteService->obtenerReporteIngresos($filtros);
        $cuentas = CuentaReceptora::orderBy('nombre')->get();
        $medios = MedioPago::cases();
        $periodos = [
            'Hoy' => [$hoy->toDateString(), $hoy->toDateString()],
            'Esta semana' => [$hoy->startOfWeek()->toDateString(), $hoy->endOfWeek()->toDateString()],
            'Este mes' => [$hoy->startOfMonth()->toDateString(), $hoy->endOfMonth()->toDateString()],
        ];

        return view('reportes.ingresos', [
            'pagos' => $reporte['items'],
            'resumen' => $reporte['resumen'],
            'filtros' => $filtros,
            'cuentas' => $cuentas,
            'medios' => $medios,
            'periodos' => $periodos,
        ]);
    }

    /**
     * Reporte de tickets y eficiencia por técnico (RF-36).
     */
    public function tickets(Request $request): View
    {
        $filtros = $this->validarFiltros($request, 'tickets');

        $reporte = $this->reporteService->obtenerReporteTickets($filtros);
        $tecnicos = Usuario::orderBy('nombre_usuario')->get();

        return view('reportes.tickets', [
            'tickets' => $reporte['items'],
            'resumen' => $reporte['resumen'],
            'filtros' => $filtros,
            'tecnicos' => $tecnicos,
        ]);
    }

    /**
     * Bandeja de alertas de vencimiento y morosidad (RF-37).
     */
    public function alertas(): View
    {
        $cuotas = $this->reporteService->obtenerAlertasVencimiento(7);

        return view('reportes.alertas', compact('cuotas'));
    }

    /**
     * Disparar recordatorio de WhatsApp para cuota próxima a vencer o vencida (RF-37).
     */
    public function notificarAlerta(Request $request, ServicioNotificacionesVencimiento $notificador): RedirectResponse
    {
        $idCuota = $request->validate(['id_cuota' => 'required|integer|exists:cuota,id_cuota'])['id_cuota'];

        $resultado = $notificador->enviar($idCuota);

        if ($resultado['enviadas'] > 0) {
            return back()->with('exito', 'Notificación de vencimiento enviada exitosamente por WhatsApp.');
        }

        if ($resultado['omitidas'] > 0) {
            return back()->with('exito', 'El aviso fue reservado u omitido según las reglas de simulación de WhatsApp.');
        }

        return back()->with('exito', 'Proceso de notificación registrado en el sistema.');
    }

    /**
     * Exportación de reportes a formatos estándar (Excel/CSV o Documento Imprimible/PDF) (RF-39).
     */
    public function exportar(Request $request, string $tipo): View|Response
    {
        $formato = $request->validate(['formato' => 'sometimes|in:csv,xlsx,pdf,imprimir'])['formato'] ?? 'csv';

        return match ($tipo) {
            'morosidad' => $this->exportarMorosidad($request, $formato),
            'ingresos' => $this->exportarIngresos($request, $formato),
            'tickets' => $this->exportarTickets($request, $formato),
            default => abort(404, 'Tipo de reporte desconocido.'),
        };
    }

    private function exportarMorosidad(Request $request, string $formato): View|Response
    {
        $filtros = $this->validarFiltros($request, 'morosidad');

        $reporte = $this->reporteService->obtenerReporteMorosidad($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return $this->documento($formato, [
                'titulo' => 'Reporte de Clientes y Estado de Morosidad',
                'subtitulo' => 'Auditoría de cartera, saldos adeudados y situación financiera de abonados',
                'tipo' => 'morosidad',
                'filtros' => $filtros,
                'resumen' => $reporte['resumen'],
                'items' => $reporte['items'],
            ]);
        }

        $encabezados = ['Cliente', 'Documento', 'Teléfono', 'Localidad', 'Plan', 'Precio Plan', 'Estado Servicio', 'Estado Financiero', 'Cuotas Vencidas', 'Deuda Total ($)', 'Días Atraso', 'Último Pago'];
        $filas = array_map(function ($item) {
            return [
                $item['cliente'],
                $item['documento'],
                $item['telefono'],
                $item['localidad'],
                $item['plan'],
                number_format($item['precio_plan'], 2, ',', '.'),
                $item['estado_servicio'],
                $item['estado_financiero'] === 'en_mora' ? 'En Mora' : 'Al Día',
                $item['cuotas_vencidas'],
                number_format($item['deuda_total'], 2, ',', '.'),
                $item['dias_atraso'],
                $item['ultimo_pago'],
            ];
        }, $reporte['items']);

        return $this->planilla($formato, 'Reporte_Morosidad_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }

    private function exportarIngresos(Request $request, string $formato): View|Response
    {
        $filtros = $this->validarFiltros($request, 'ingresos');

        $reporte = $this->reporteService->obtenerReporteIngresos($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return $this->documento($formato, [
                'titulo' => 'Reporte Financiero de Ingresos y Recaudación',
                'subtitulo' => sprintf('Período: %s al %s', date('d/m/Y', strtotime($reporte['resumen']['desde'])), date('d/m/Y', strtotime($reporte['resumen']['hasta']))),
                'tipo' => 'ingresos',
                'filtros' => $filtros,
                'resumen' => $reporte['resumen'],
                'items' => $reporte['items'],
            ]);
        }

        $encabezados = ['ID Pago', 'Fecha', 'Cliente', 'Documento', 'Medio de Pago', 'Cuenta Receptora', 'Monto ($)', 'Nro Comprobante / Ref'];
        $filas = [];
        foreach ($reporte['items'] as $pago) {
            $cliente = $pago->cuotas->first()?->servicio?->cliente;
            $filas[] = [
                $pago->id_pago,
                $pago->fecha?->format('d/m/Y') ?? '-',
                $cliente?->nombre_razon_social ?? 'Cliente General',
                $cliente?->numero_documento ?? '-',
                $pago->medio_pago instanceof \BackedEnum ? $pago->medio_pago->value : (string) $pago->medio_pago,
                $pago->cuenta?->nombre ?? 'Caja Central',
                number_format((float) $pago->monto_total, 2, ',', '.'),
                $pago->comprobante?->id_comprobante ? 'Comp #'.$pago->comprobante->id_comprobante : 'Directo',
            ];
        }

        return $this->planilla($formato, 'Reporte_Ingresos_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }

    private function exportarTickets(Request $request, string $formato): View|Response
    {
        $filtros = $this->validarFiltros($request, 'tickets');

        $reporte = $this->reporteService->obtenerReporteTickets($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return $this->documento($formato, [
                'titulo' => 'Reporte de Eficiencia y Resolución de Tickets',
                'subtitulo' => sprintf('Período: %s al %s', date('d/m/Y', strtotime($reporte['resumen']['desde'])), date('d/m/Y', strtotime($reporte['resumen']['hasta']))),
                'tipo' => 'tickets',
                'filtros' => $filtros,
                'resumen' => $reporte['resumen'],
                'items' => $reporte['items'],
            ]);
        }

        $encabezados = ['# Ticket', 'Fecha Creación', 'Cliente', 'Tipo', 'Estado', 'Técnico Asignado', 'Fecha Asignación', 'Fecha Resolución', 'Horas Resolución'];
        $filas = [];
        foreach ($reporte['items'] as $ticket) {
            $horas = ($ticket->fecha_creacion && $ticket->fecha_resolucion)
                ? round($ticket->fecha_creacion->diffInHours($ticket->fecha_resolucion), 1)
                : '-';

            $filas[] = [
                $ticket->id_ticket,
                $ticket->fecha_creacion?->format('d/m/Y H:i') ?? '-',
                $ticket->servicio?->cliente?->nombre_razon_social ?? 'No especificado',
                $ticket->tipo,
                $ticket->estado instanceof \BackedEnum ? $ticket->estado->value : (string) $ticket->estado,
                $ticket->usuarioAsignado?->nombre_usuario ?? 'Sin asignar',
                $ticket->fecha_asignacion?->format('d/m/Y H:i') ?? '-',
                $ticket->fecha_resolucion?->format('d/m/Y H:i') ?? '-',
                $horas,
            ];
        }

        return $this->planilla($formato, 'Reporte_Tickets_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }

    /** @return array<string, mixed> */
    private function validarFiltros(Request $request, string $tipo): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $periodo = [
            'fecha_desde' => $hoy->startOfMonth()->toDateString(),
            'fecha_hasta' => $hoy->endOfMonth()->toDateString(),
        ];
        $predeterminados = match ($tipo) {
            'morosidad' => ['estado_financiero' => 'todos', 'id_plan' => null, 'busqueda' => null],
            'ingresos' => $periodo + ['medio_pago' => 'todos', 'id_cuenta' => null],
            'tickets' => $periodo + ['estado' => 'todos', 'id_usuario_asignado' => null, 'tipo' => null],
        };
        $datos = array_replace($predeterminados, array_intersect_key($request->query(), $predeterminados));
        foreach ($predeterminados as $campo => $valor) {
            $datos[$campo] ??= $valor;
        }

        $reglasPeriodo = [
            'fecha_desde' => ['bail', 'required', 'date_format:Y-m-d'],
            'fecha_hasta' => ['bail', 'required', 'date_format:Y-m-d', ...(is_string($datos['fecha_desde'] ?? null) ? ['after_or_equal:fecha_desde'] : [])],
        ];
        $reglas = match ($tipo) {
            'morosidad' => [
                'estado_financiero' => ['required', Rule::in(['todos', 'al_dia', 'en_mora'])],
                'id_plan' => ['bail', 'nullable', 'integer', 'exists:plan,id_plan'],
                'busqueda' => ['nullable', 'string', 'max:200'],
            ],
            'ingresos' => $reglasPeriodo + [
                'medio_pago' => ['required', Rule::in(['todos', ...array_column(MedioPago::cases(), 'value')])],
                'id_cuenta' => ['bail', 'nullable', 'integer', 'exists:cuenta_receptora,id_cuenta'],
            ],
            'tickets' => $reglasPeriodo + [
                'estado' => ['required', Rule::in(['todos', ...array_column(EstadoTicket::cases(), 'value')])],
                'id_usuario_asignado' => [
                    'bail', 'nullable', 'regex:/^(sin_asignar|[1-9][0-9]*)$/',
                    Rule::when($datos['id_usuario_asignado'] !== 'sin_asignar', 'exists:usuario,id_usuario'),
                ],
                'tipo' => ['nullable', Rule::in(['tecnico', 'administrativo', 'instalacion', 'reclamo'])],
            ],
        };
        $validador = Validator::make($datos, $reglas, [
            'required' => 'Completá el campo :attribute.',
            'date_format' => 'El campo :attribute debe contener una fecha válida.',
            'after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            'exists' => 'La opción seleccionada en :attribute ya no está disponible.',
            'in' => 'Seleccioná una opción válida para :attribute.',
            'integer' => 'Seleccioná una opción válida para :attribute.',
            'regex' => 'Seleccioná un responsable válido.',
            'string' => 'El campo :attribute debe ser un texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
        ], [
            'fecha_desde' => 'fecha desde', 'fecha_hasta' => 'fecha hasta',
            'estado_financiero' => 'situación financiera', 'id_plan' => 'plan',
            'busqueda' => 'búsqueda', 'medio_pago' => 'medio de pago',
            'id_cuenta' => 'cuenta receptora', 'estado' => 'estado del ticket',
            'id_usuario_asignado' => 'responsable', 'tipo' => 'tipo de ticket',
        ]);
        if ($validador->fails()) {
            throw (new ValidationException($validador))->redirectTo(route('reportes.'.$tipo));
        }

        return $validador->validated();
    }

    private function documento(string $formato, array $datos): View|Response
    {
        return $formato === 'pdf'
            ? app(ExportService::class)->pdf($datos)
            : view('reportes.imprimir', $datos);
    }

    private function planilla(string $formato, string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return $formato === 'xlsx'
            ? app(ExportService::class)->excel($nombre, $encabezados, $filas)
            : $this->reporteService->exportarCsv($nombre, $encabezados, $filas);
    }
}
