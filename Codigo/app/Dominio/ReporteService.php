<?php

namespace App\Dominio;

use App\Enums\EstadoCliente;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio ReporteService
 * Responsable de la agregación de métricas, cálculo de KPIs y exportación tabular
 * para el Módulo 5 (Reportes y Dashboard Ejecutivo).
 */
class ReporteService
{
    /**
     * Caso de Uso: Consultar Dashboard General con KPIs ejecutivos (Módulo 5)
     * Clase UML: ReporteService | Método UML: + obtenerMetricasDashboard() : array
     * Requerimientos: RF-38, RF-37
     *
     * Calcula los KPIs generales y las series estadísticas para el Dashboard Ejecutivo.
     *
     * @return array<string, mixed>
     */
    public function obtenerMetricasDashboard(): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $inicioMesActual = $hoy->startOfMonth()->toDateString();
        $finMesActual = $hoy->endOfMonth()->toDateString();
        $inicioMesAnterior = $hoy->startOfMonth()->subMonth()->toDateString();
        $finMesAnterior = $hoy->startOfMonth()->subMonth()->endOfMonth()->toDateString();

        // 1. Clientes y Servicios
        $totalClientes = Cliente::count();
        $clientesActivos = Cliente::where('estado', EstadoCliente::Activo->value)->count();
        $serviciosActivos = Servicio::where('estado', EstadoServicio::Activo->value)->count();

        // Clientes con cuotas en mora
        $clientesEnMoraCount = Cliente::whereHas('cuotas', function (Builder $q) use ($hoy): void {
            $q->whereNull('cuota.id_pago')
                ->where(function (Builder $sub) use ($hoy): void {
                    $sub->where('cuota.estado', EstadoCuota::Vencida->value)
                        ->orWhere('cuota.fecha_vencimiento', '<', $hoy->toDateString());
                });
        })->count();

        $porcentajeMorosidad = $totalClientes > 0
            ? round(($clientesEnMoraCount / $totalClientes) * 100, 1)
            : 0.0;

        // Monto total adeudado (cuotas pendientes o vencidas cuya fecha de vencimiento ya expiró)
        $montoTotalDeuda = (float) Cuota::whereNull('id_pago')
            ->where(function (Builder $q) use ($hoy): void {
                $q->where('estado', EstadoCuota::Vencida->value)
                    ->orWhere('fecha_vencimiento', '<', $hoy->toDateString());
            })->sum('monto');

        // 2. Cobranzas e Ingresos
        $recaudacionMesActual = (float) Pago::whereBetween('fecha', [$inicioMesActual, $finMesActual])->sum('monto_total');
        $recaudacionMesAnterior = (float) Pago::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->sum('monto_total');

        $variacionRecaudacionPct = $recaudacionMesAnterior > 0
            ? round((($recaudacionMesActual - $recaudacionMesAnterior) / $recaudacionMesAnterior) * 100, 1)
            : ($recaudacionMesActual > 0 ? 100.0 : 0.0);

        // 3. Tickets de Soporte
        $ticketsTotal = Ticket::count();
        $ticketsPendientes = Ticket::where('estado', EstadoTicket::Pendiente->value)->count();
        $ticketsEnProceso = Ticket::whereIn('estado', [EstadoTicket::Asignado->value, EstadoTicket::EnProceso->value])->count();
        $ticketsResueltos = Ticket::where('estado', EstadoTicket::Resuelto->value)->count();
        $ticketsCerrados = Ticket::where('estado', EstadoTicket::Cerrado->value)->count();

        // 4. Comprobantes pendientes
        $comprobantesPendientes = Comprobante::where('estado_validacion', EstadoComprobante::Pendiente->value)->count();

        // 5. Serie histórica de ingresos (últimos 6 meses para Chart.js)
        $serieIngresos = [];
        for ($i = 5; $i >= 0; $i--) {
            $mesRef = $hoy->startOfMonth()->subMonths($i);
            $ini = $mesRef->startOfMonth()->toDateString();
            $fin = $mesRef->endOfMonth()->toDateString();
            $totalMes = (float) Pago::whereBetween('fecha', [$ini, $fin])->sum('monto_total');

            $serieIngresos[] = [
                'mes' => $mesRef->translatedFormat('M Y'),
                'total' => $totalMes,
            ];
        }

        // 6. Distribución de servicios por Plan
        $distribucionPlanes = Plan::withCount(['servicios' => function ($q) {
            $q->where('estado', EstadoServicio::Activo->value);
        }])->get()->map(fn (Plan $plan) => [
            'nombre' => $plan->nombre,
            'cantidad' => $plan->servicios_count,
        ])->toArray();

        // 7. Alertas de vencimiento inminente (próximos 5 días o ya vencidas)
        $alertasVencimiento = $this->consultaAlertasVencimiento($hoy, 5)
            ->limit(6)
            ->get();

        return [
            'total_clientes' => $totalClientes,
            'clientes_activos' => $clientesActivos,
            'servicios_activos' => $serviciosActivos,
            'clientes_en_mora' => $clientesEnMoraCount,
            'porcentaje_morosidad' => $porcentajeMorosidad,
            'monto_total_deuda' => $montoTotalDeuda,
            'recaudacion_mes_actual' => $recaudacionMesActual,
            'recaudacion_mes_anterior' => $recaudacionMesAnterior,
            'variacion_recaudacion_pct' => $variacionRecaudacionPct,
            'tickets_total' => $ticketsTotal,
            'tickets_pendientes' => $ticketsPendientes,
            'tickets_en_proceso' => $ticketsEnProceso,
            'tickets_resueltos' => $ticketsResueltos,
            'tickets_cerrados' => $ticketsCerrados,
            'comprobantes_pendientes' => $comprobantesPendientes,
            'serie_ingresos' => $serieIngresos,
            'distribucion_planes' => $distribucionPlanes,
            'alertas_vencimiento' => $alertasVencimiento,
        ];
    }

    /**
     * Caso de Uso: Generar reporte de clientes al día y en mora (Módulo 5)
     * Clase UML: ReporteService | Método UML: + obtenerReporteMorosidad(filtros: array) : array
     * Requerimiento: RF-34
     *
     * Genera el dataset del reporte de clientes al día y en mora.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{items: array<int, array<string, mixed>>, resumen: array<string, mixed>}
     */
    public function obtenerReporteMorosidad(array $filtros = []): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');

        $ultimosPagos = Pago::query()
            ->select('servicio.id_cliente')
            ->selectRaw('MAX(pago.fecha) AS ultima_fecha')
            ->join('cuota', 'cuota.id_pago', '=', 'pago.id_pago')
            ->join('servicio', 'servicio.id_servicio', '=', 'cuota.id_servicio')
            ->groupBy('servicio.id_cliente');
        $query = Cliente::query()
            ->select('cliente.*', 'ultimos_pagos.ultima_fecha AS ultimo_pago_fecha')
            ->leftJoinSub($ultimosPagos, 'ultimos_pagos', 'ultimos_pagos.id_cliente', '=', 'cliente.id_cliente')
            ->withCasts(['ultimo_pago_fecha' => 'date'])
            ->with([
                'servicios.plan',
                'cuotas' => function ($q) use ($hoy) {
                    $q->whereNull('cuota.id_pago')
                        ->where(function (Builder $cuotas) use ($hoy) {
                            $cuotas->where('cuota.estado', EstadoCuota::Vencida->value)
                                ->orWhere('cuota.fecha_vencimiento', '<', $hoy->toDateString());
                        })
                        ->orderBy('cuota.fecha_vencimiento');
                },
            ]);

        if (! empty($filtros['busqueda'])) {
            $busqueda = '%'.trim((string) $filtros['busqueda']).'%';
            $query->where(function (Builder $sub) use ($busqueda) {
                $sub->where('nombre_razon_social', 'like', $busqueda)
                    ->orWhere('numero_documento', 'like', $busqueda)
                    ->orWhere('telefono_whatsapp', 'like', $busqueda);
            });
        }

        if (! empty($filtros['id_plan'])) {
            $query->whereHas('servicios', function (Builder $q) use ($filtros) {
                $q->where('id_plan', $filtros['id_plan']);
            });
        }

        $clientes = $query->orderBy('nombre_razon_social')->orderBy('cliente.id_cliente')->get();

        $items = [];
        $totalDeudaAcumulada = 0.0;
        $totalAlDia = 0;
        $totalEnMora = 0;

        foreach ($clientes as $cliente) {
            $cuotasImpagas = $cliente->cuotas;

            $montoDeuda = (float) $cuotasImpagas->sum('monto');
            $cantCuotasVencidas = $cuotasImpagas->count();
            $esMoroso = $cantCuotasVencidas > 0;

            // Filtro por estado financiero
            $filtroEstado = $filtros['estado_financiero'] ?? 'todos';
            if ($filtroEstado === 'al_dia' && $esMoroso) {
                continue;
            }
            if ($filtroEstado === 'en_mora' && ! $esMoroso) {
                continue;
            }

            if ($esMoroso) {
                $totalEnMora++;
                $totalDeudaAcumulada += $montoDeuda;
            } else {
                $totalAlDia++;
            }

            $cuotaMasAntigua = $cuotasImpagas->first();
            $diasAtraso = $cuotaMasAntigua
                ? max(0, (int) CarbonImmutable::parse($cuotaMasAntigua->fecha_vencimiento->toDateString(), $hoy->timezone)->diffInDays($hoy, false))
                : 0;

            $servicioPrincipal = $cliente->servicios->first();
            $planNombre = $servicioPrincipal?->plan?->nombre ?? 'Sin plan';
            $planPrecio = (float) ($servicioPrincipal?->plan?->precio_vigente ?? 0);

            $items[] = [
                'id_cliente' => $cliente->id_cliente,
                'cliente' => $cliente->nombre_razon_social,
                'documento' => $cliente->numero_documento,
                'telefono' => $cliente->telefono_whatsapp ?: 'Sin teléfono',
                'localidad' => $cliente->localidad_contacto ?: 'Villafañe',
                'plan' => $planNombre,
                'precio_plan' => $planPrecio,
                'estado_servicio' => $servicioPrincipal?->estado?->value ?? 'sin_servicio',
                'estado_financiero' => $esMoroso ? 'en_mora' : 'al_dia',
                'cuotas_vencidas' => $cantCuotasVencidas,
                'deuda_total' => $montoDeuda,
                'dias_atraso' => $diasAtraso,
                'ultimo_pago' => $cliente->ultimo_pago_fecha?->format('d/m/Y') ?? 'Sin registros',
            ];
        }

        $totalEvaluados = count($items);
        $porcentajeMora = $totalEvaluados > 0
            ? round(($totalEnMora / $totalEvaluados) * 100, 1)
            : 0.0;

        return [
            'items' => $items,
            'resumen' => [
                'total_evaluados' => $totalEvaluados,
                'total_al_dia' => $totalAlDia,
                'total_en_mora' => $totalEnMora,
                'porcentaje_mora' => $porcentajeMora,
                'monto_total_deuda' => $totalDeudaAcumulada,
            ],
        ];
    }

    /**
     * Caso de Uso: Generar reporte financiero de ingresos por período (Módulo 5)
     * Clase UML: ReporteService | Método UML: + obtenerReporteIngresos(filtros: array) : array
     * Requerimiento: RF-35
     *
     * Genera el dataset del reporte financiero de ingresos por período.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{items: Collection<int, Pago>, resumen: array<string, mixed>}
     */
    public function obtenerReporteIngresos(array $filtros = []): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $desde = ! empty($filtros['fecha_desde']) ? $filtros['fecha_desde'] : $hoy->startOfMonth()->toDateString();
        $hasta = ! empty($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : $hoy->endOfMonth()->toDateString();

        $query = Pago::query()
            ->with([
                'cuenta',
                'comprobante',
                'cuotas.servicio.cliente',
            ])
            ->whereBetween('fecha', [$desde, $hasta]);

        if (! empty($filtros['medio_pago']) && $filtros['medio_pago'] !== 'todos') {
            $query->where('medio_pago', $filtros['medio_pago']);
        }

        if (! empty($filtros['id_cuenta'])) {
            $query->where('id_cuenta', $filtros['id_cuenta']);
        }

        $pagos = $query->orderBy('fecha', 'desc')->orderBy('id_pago', 'desc')->get();

        $totalRecaudado = (float) $pagos->sum('monto_total');
        $cantidadPagos = $pagos->count();
        $ticketPromedio = $cantidadPagos > 0 ? round($totalRecaudado / $cantidadPagos, 2) : 0.0;

        // Desglose por medio de pago
        $desgloseMedios = [];
        foreach ($pagos->groupBy('medio_pago') as $medio => $grupo) {
            $subtotal = (float) $grupo->sum('monto_total');
            $desgloseMedios[] = [
                'medio' => $medio instanceof \BackedEnum ? $medio->value : (string) $medio,
                'cantidad' => $grupo->count(),
                'subtotal' => $subtotal,
                'porcentaje' => $totalRecaudado > 0 ? round(($subtotal / $totalRecaudado) * 100, 1) : 0.0,
            ];
        }

        return [
            'items' => $pagos,
            'resumen' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'total_recaudado' => $totalRecaudado,
                'cantidad_pagos' => $cantidadPagos,
                'ticket_promedio' => $ticketPromedio,
                'desglose_medios' => $desgloseMedios,
            ],
        ];
    }

    /**
     * Caso de Uso: Generar reporte de tickets y eficiencia por técnico (Módulo 5)
     * Clase UML: ReporteService | Método UML: + obtenerReporteTickets(filtros: array) : array
     * Requerimiento: RF-36
     *
     * Genera el dataset del reporte de tickets y eficiencia por técnico.
     *
     * @param  array<string, mixed>  $filtros
     * @return array{items: Collection<int, Ticket>, resumen: array<string, mixed>}
     */
    public function obtenerReporteTickets(array $filtros = []): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $desde = ! empty($filtros['fecha_desde']) ? $filtros['fecha_desde'] : $hoy->startOfMonth()->toDateString();
        $hasta = ! empty($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : $hoy->endOfMonth()->toDateString();

        $query = Ticket::query()
            ->with(['servicio.cliente', 'usuarioAsignado'])
            ->whereBetween('fecha_creacion', [$desde.' 00:00:00', $hasta.' 23:59:59']);

        if (! empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['id_usuario_asignado'])) {
            if ($filtros['id_usuario_asignado'] === 'sin_asignar') {
                $query->whereNull('id_usuario_asignado');
            } else {
                $query->where('id_usuario_asignado', $filtros['id_usuario_asignado']);
            }
        }

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        $tickets = $query->orderBy('fecha_creacion', 'desc')->get();

        $totalTickets = $tickets->count();
        $resueltos = $tickets->filter(fn (Ticket $t) => in_array($t->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado]));
        $cantResueltos = $resueltos->count();

        $tasaResolucion = $totalTickets > 0 ? round(($cantResueltos / $totalTickets) * 100, 1) : 0.0;

        // Cálculo de MTTR (Mean Time to Resolution en horas)
        $tiemposResolucionHoras = [];
        foreach ($resueltos as $ticket) {
            if ($ticket->fecha_resolucion && $ticket->fecha_creacion) {
                $horas = $ticket->fecha_creacion->diffInHours($ticket->fecha_resolucion);
                $tiemposResolucionHoras[] = $horas;
            }
        }

        $tiempoPromedioHoras = count($tiemposResolucionHoras) > 0
            ? round(array_sum($tiemposResolucionHoras) / count($tiemposResolucionHoras), 1)
            : 0.0;

        // Distribución por técnico
        $porTecnico = [];
        foreach ($tickets->groupBy('id_usuario_asignado') as $idUsuario => $grupo) {
            $nombre = $grupo->first()->usuarioAsignado?->nombre_usuario ?? 'Sin asignar';
            $porTecnico[] = [
                'usuario' => $nombre,
                'total' => $grupo->count(),
                'resueltos' => $grupo->filter(fn ($t) => in_array($t->estado, [EstadoTicket::Resuelto, EstadoTicket::Cerrado]))->count(),
            ];
        }

        return [
            'items' => $tickets,
            'resumen' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'total_tickets' => $totalTickets,
                'tickets_resueltos' => $cantResueltos,
                'tasa_resolucion' => $tasaResolucion,
                'tiempo_promedio_horas' => $tiempoPromedioHoras,
                'por_tecnico' => $porTecnico,
            ],
        ];
    }

    /**
     * Caso de Uso: Visualizar alertas de vencimiento y morosidad (Módulo 5)
     * Clase UML: ReporteService | Método UML: + obtenerReporteAlertasVencimiento() : array
     * Requerimiento: RF-37
     *
     * Obtiene cuotas en alerta de vencimiento.
     *
     * @return array{cuotas: LengthAwarePaginator<int, Cuota>, vencidas: int, monto: float, hoy: string}
     */
    public function obtenerReporteAlertasVencimiento(): array
    {
        $hoy = CarbonImmutable::today('America/Argentina/Buenos_Aires');
        $consulta = $this->consultaAlertasVencimiento($hoy, 7);
        $totales = (clone $consulta)->reorder()->toBase()
            ->selectRaw('COUNT(*) AS total, SUM(monto) AS monto, SUM(CASE WHEN fecha_vencimiento < ? THEN 1 ELSE 0 END) AS vencidas', [$hoy->toDateString()])
            ->first();

        return [
            'cuotas' => $consulta->paginate(20, total: (int) $totales->total),
            'vencidas' => (int) $totales->vencidas,
            'monto' => (float) $totales->monto,
            'hoy' => $hoy->toDateString(),
        ];
    }

    /** @return Builder<Cuota> */
    private function consultaAlertasVencimiento(CarbonImmutable $hoy, int $diasAnticipacion): Builder
    {
        $limite = $hoy->addDays($diasAnticipacion)->toDateString();

        return Cuota::query()
            ->with(['servicio.cliente', 'servicio.plan'])
            ->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->where('fecha_vencimiento', '<=', $limite)
            ->orderBy('fecha_vencimiento')
            ->orderBy('id_cuota');
    }

    /**
     * Caso de Uso: Exportar reporte a formato Excel / CSV (Auditoría contable) (Módulo 5)
     * Clase UML: ReporteService | Método UML: + exportarCsv(nombreArchivo: String, encabezados: array, filas: iterable) : StreamedResponse
     * Requerimiento: RF-39
     *
     * Genera una respuesta de descarga en formato CSV UTF-8 con BOM compatible con Microsoft Excel.
     *
     * @param  array<int, string>  $encabezados
     * @param  iterable<array<int, mixed>>  $filas
     */
    public function exportarCsv(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($encabezados, $filas): void {
            $handle = fopen('php://output', 'w');
            // Escribir Byte Order Mark (BOM) para UTF-8 para que Excel reconozca tildes y caracteres especiales
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $encabezados, ';');

            foreach ($filas as $fila) {
                fputcsv($handle, $fila, ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.csv"', $nombreArchivo));
        $response->headers->set('Cache-Control', 'no-store, no-cache');

        return $response;
    }
}
