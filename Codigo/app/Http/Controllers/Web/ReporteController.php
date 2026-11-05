<?php

namespace App\Http\Controllers\Web;

use App\Dominio\ReporteService;
use App\Dominio\ServicioNotificacionesVencimiento;
use App\Enums\MedioPago;
use App\Http\Controllers\Controller;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Plan;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
        $filtros = [
            'estado_financiero' => $request->query('estado_financiero', 'todos'),
            'id_plan' => $request->query('id_plan'),
            'busqueda' => $request->query('busqueda'),
        ];

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
        $filtros = [
            'fecha_desde' => $request->query('fecha_desde', now()->startOfMonth()->toDateString()),
            'fecha_hasta' => $request->query('fecha_hasta', now()->endOfMonth()->toDateString()),
            'medio_pago' => $request->query('medio_pago', 'todos'),
            'id_cuenta' => $request->query('id_cuenta'),
        ];

        $reporte = $this->reporteService->obtenerReporteIngresos($filtros);
        $cuentas = CuentaReceptora::orderBy('nombre')->get();
        $medios = MedioPago::cases();

        return view('reportes.ingresos', [
            'pagos' => $reporte['items'],
            'resumen' => $reporte['resumen'],
            'filtros' => $filtros,
            'cuentas' => $cuentas,
            'medios' => $medios,
        ]);
    }

    /**
     * Reporte de tickets y eficiencia por técnico (RF-36).
     */
    public function tickets(Request $request): View
    {
        $filtros = [
            'fecha_desde' => $request->query('fecha_desde', now()->startOfMonth()->toDateString()),
            'fecha_hasta' => $request->query('fecha_hasta', now()->endOfMonth()->toDateString()),
            'estado' => $request->query('estado', 'todos'),
            'id_usuario_asignado' => $request->query('id_usuario_asignado'),
            'tipo' => $request->query('tipo'),
        ];

        $reporte = $this->reporteService->obtenerReporteTickets($filtros);
        $tecnicos = Usuario::whereHas('empleado')->orderBy('nombre_usuario')->get();

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
    public function exportar(Request $request, string $tipo): View|StreamedResponse
    {
        $formato = $request->query('formato', 'csv');

        return match ($tipo) {
            'morosidad' => $this->exportarMorosidad($request, $formato),
            'ingresos' => $this->exportarIngresos($request, $formato),
            'tickets' => $this->exportarTickets($request, $formato),
            default => abort(404, 'Tipo de reporte desconocido.'),
        };
    }

    private function exportarMorosidad(Request $request, string $formato): View|StreamedResponse
    {
        $filtros = [
            'estado_financiero' => $request->query('estado_financiero', 'todos'),
            'id_plan' => $request->query('id_plan'),
            'busqueda' => $request->query('busqueda'),
        ];

        $reporte = $this->reporteService->obtenerReporteMorosidad($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return view('reportes.imprimir', [
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

        return $this->reporteService->exportarCsv('Reporte_Morosidad_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }

    private function exportarIngresos(Request $request, string $formato): View|StreamedResponse
    {
        $filtros = [
            'fecha_desde' => $request->query('fecha_desde', now()->startOfMonth()->toDateString()),
            'fecha_hasta' => $request->query('fecha_hasta', now()->endOfMonth()->toDateString()),
            'medio_pago' => $request->query('medio_pago', 'todos'),
            'id_cuenta' => $request->query('id_cuenta'),
        ];

        $reporte = $this->reporteService->obtenerReporteIngresos($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return view('reportes.imprimir', [
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

        return $this->reporteService->exportarCsv('Reporte_Ingresos_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }

    private function exportarTickets(Request $request, string $formato): View|StreamedResponse
    {
        $filtros = [
            'fecha_desde' => $request->query('fecha_desde', now()->startOfMonth()->toDateString()),
            'fecha_hasta' => $request->query('fecha_hasta', now()->endOfMonth()->toDateString()),
            'estado' => $request->query('estado', 'todos'),
            'id_usuario_asignado' => $request->query('id_usuario_asignado'),
            'tipo' => $request->query('tipo'),
        ];

        $reporte = $this->reporteService->obtenerReporteTickets($filtros);

        if ($formato === 'pdf' || $formato === 'imprimir') {
            return view('reportes.imprimir', [
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

        return $this->reporteService->exportarCsv('Reporte_Tickets_VillafaneWifi_'.date('Ymd_His'), $encabezados, $filas);
    }
}
