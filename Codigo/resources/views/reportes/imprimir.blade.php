<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} - Villafañe Wifi</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            color: #1a202c;
            background: #f8fafc;
            padding: 30px;
            font-size: 12px;
            line-height: 1.4;
        }
        .hoja-impresion {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .membrete {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #00838F;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .membrete-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-simbolo {
            background: #00838F;
            color: #fff;
            font-weight: 800;
            font-size: 18px;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .membrete-titulo h1 {
            font-size: 18px;
            color: #00838F;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }
        .membrete-titulo p {
            color: #64748b;
            font-size: 11px;
        }
        .membrete-datos {
            text-align: right;
            font-size: 10px;
            color: #475569;
            line-height: 1.35;
        }
        .info-reporte {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .info-item strong {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
        }
        .info-item span {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .resumen-cajas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .caja-resumen {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            background: #ffffff;
        }
        .caja-resumen span {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            display: block;
        }
        .caja-resumen strong {
            font-size: 16px;
            color: #00838F;
            margin-top: 4px;
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 11px;
        }
        th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
        }
        .badge-verde { background: #dcfce7; color: #166534; }
        .badge-rojo { background: #fee2e2; color: #991b1b; }
        .badge-amarillo { background: #fef9c3; color: #854d0e; }
        .badge-azul { background: #e0f2fe; color: #075985; }

        .firmas {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            padding: 0 40px;
        }
        .linea-firma {
            width: 200px;
            border-top: 1px solid #475569;
            text-align: center;
            padding-top: 6px;
            font-size: 10px;
            color: #475569;
        }

        .barra-acciones {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 999;
        }
        .btn-accion {
            background: #00838F;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-accion:hover { background: #006064; }
        .btn-volver { background: #475569; }
        .btn-volver:hover { background: #334155; }

        @media print {
            body { background: #fff; padding: 0; }
            .hoja-impresion { box-shadow: none; padding: 0; max-width: 100%; }
            .barra-acciones { display: none; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>

<div class="barra-acciones">
    <a href="javascript:window.history.back()" class="btn-accion btn-volver">← Volver al Panel</a>
    <button onclick="window.print()" class="btn-accion">🖨️ Imprimir / Guardar como PDF</button>
</div>

<div class="hoja-impresion">
    {{-- Membrete Oficial --}}
    <header class="membrete">
        <div class="membrete-logo">
            <span class="logo-simbolo">VW</span>
            <div class="membrete-titulo">
                <h1>Villafañe Wifi</h1>
                <p>Proveedor de Servicios de Internet & Telecomunicaciones</p>
            </div>
        </div>
        <div class="membrete-datos">
            <strong>VILLAFAÑE WIFI S.R.L.</strong><br>
            CUIT: 30-71458923-9<br>
            Av. San Martín 450 – Mayor Villafañe, Formosa<br>
            Teléfono: +54 9 370 412-3456 · soporte@villafanewifi.com.ar
        </div>
    </header>

    {{-- Encabezado del Reporte --}}
    <div style="margin-bottom: 16px;">
        <h2 style="font-size: 16px; color: #0f172a; text-transform: uppercase;">{{ $titulo }}</h2>
        <p style="color: #64748b; font-size: 11px;">{{ $subtitulo }}</p>
    </div>

    <div class="info-reporte">
        <div class="info-item">
            <strong>Fecha y Hora de Emisión</strong>
            <span>{{ now()->format('d/m/Y H:i:s') }}</span>
        </div>
        <div class="info-item">
            <strong>Usuario Responsable</strong>
            <span>{{ auth()->user()->nombre_usuario ?? 'Sistema' }}</span>
        </div>
        <div class="info-item">
            <strong>Validez Oficial</strong>
            <span>Documento Oficial Certificado</span>
        </div>
        <div class="info-item">
            <strong>Módulo del Sistema</strong>
            <span>Módulo 5 – Reportes y KPIs (RF-39)</span>
        </div>
    </div>

    {{-- Resumen de Métricas según el tipo de reporte --}}
    @if ($tipo === 'morosidad')
        <div class="resumen-cajas">
            <div class="caja-resumen">
                <span>Total Evaluados</span>
                <strong>{{ $resumen['total_evaluados'] }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Al Día</span>
                <strong style="color: #16a34a;">{{ $resumen['total_al_dia'] }}</strong>
            </div>
            <div class="caja-resumen">
                <span>En Mora</span>
                <strong style="color: #dc2626;">{{ $resumen['total_en_mora'] }} ({{ $resumen['porcentaje_mora'] }}%)</strong>
            </div>
            <div class="caja-resumen">
                <span>Deuda Total Acumulada</span>
                <strong style="color: #dc2626;">${{ number_format($resumen['monto_total_deuda'], 2, ',', '.') }}</strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Cliente / Abonado</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Plan</th>
                    <th>Situación</th>
                    <th style="text-align: center;">Cuotas Impagas</th>
                    <th style="text-align: right;">Deuda Total</th>
                    <th>Último Pago</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td><strong>{{ $item['cliente'] }}</strong><br><small style="color: #64748b;">{{ $item['localidad'] }}</small></td>
                        <td>{{ $item['documento'] }}</td>
                        <td>{{ $item['telefono'] }}</td>
                        <td>{{ $item['plan'] }}</td>
                        <td>
                            @if ($item['estado_financiero'] === 'en_mora')
                                <span class="badge badge-rojo">EN MORA</span>
                            @else
                                <span class="badge badge-verde">AL DÍA</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item['cuotas_vencidas'] }}</td>
                        <td style="text-align: right; font-weight: 700; color: {{ $item['deuda_total'] > 0 ? '#dc2626' : '#16a34a' }};">
                            ${{ number_format($item['deuda_total'], 2, ',', '.') }}
                        </td>
                        <td>{{ $item['ultimo_pago'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">No se registraron clientes bajo el criterio filtrado.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tipo === 'ingresos')
        <div class="resumen-cajas">
            <div class="caja-resumen">
                <span>Total Recaudado</span>
                <strong style="color: #16a34a;">${{ number_format($resumen['total_recaudado'], 2, ',', '.') }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Cobranzas Registradas</span>
                <strong>{{ $resumen['cantidad_pagos'] }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Ticket Promedio</span>
                <strong>${{ number_format($resumen['ticket_promedio'], 2, ',', '.') }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Período Auditado</span>
                <strong style="font-size: 13px;">{{ date('d/m/Y', strtotime($resumen['desde'])) }} al {{ date('d/m/Y', strtotime($resumen['hasta'])) }}</strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th># Pago</th>
                    <th>Fecha</th>
                    <th>Cliente / Titular</th>
                    <th>Medio de Pago</th>
                    <th>Cuenta Receptora</th>
                    <th>Período / Cuota</th>
                    <th style="text-align: right;">Importe Imputado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $pago)
                    @php
                        $cuota = $pago->cuotas->first();
                        $cliente = $cuota?->servicio?->cliente;
                    @endphp
                    <tr>
                        <td><strong>#{{ $pago->id_pago }}</strong></td>
                        <td>{{ $pago->fecha?->format('d/m/Y') }}</td>
                        <td>
                            <strong>{{ $cliente?->nombre_razon_social ?? 'Abonado General' }}</strong><br>
                            <small style="color: #64748b;">DNI: {{ $cliente?->numero_documento ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-verde">
                                {{ ucfirst(str_replace('_', ' ', $pago->medio_pago instanceof \BackedEnum ? $pago->medio_pago->value : (string) $pago->medio_pago)) }}
                            </span>
                        </td>
                        <td>{{ $pago->cuenta?->nombre ?? 'Caja Central' }}</td>
                        <td>{{ $cuota ? 'Cuota '.$cuota->fecha_vencimiento?->format('m/Y') : 'Cobro Directo' }}</td>
                        <td style="text-align: right; font-weight: 700; color: #16a34a;">
                            ${{ number_format((float) $pago->monto_total, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">No se registraron cobranzas en el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tipo === 'tickets')
        <div class="resumen-cajas">
            <div class="caja-resumen">
                <span>Total Incidentes</span>
                <strong>{{ $resumen['total_tickets'] }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Tickets Resueltos</span>
                <strong style="color: #16a34a;">{{ $resumen['tickets_resueltos'] }}</strong>
            </div>
            <div class="caja-resumen">
                <span>Tasa de Resolución</span>
                <strong style="color: #0284c7;">{{ $resumen['tasa_resolucion'] }}%</strong>
            </div>
            <div class="caja-resumen">
                <span>Tiempo Medio (MTTR)</span>
                <strong>{{ $resumen['tiempo_promedio_horas'] }} hs</strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th># Ticket</th>
                    <th>Fecha Alta</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Técnico Responsable</th>
                    <th>Estado</th>
                    <th>Resolución</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $ticket)
                    @php
                        $cliente = $ticket->servicio?->cliente;
                        $horas = ($ticket->fecha_creacion && $ticket->fecha_resolucion)
                            ? round($ticket->fecha_creacion->diffInHours($ticket->fecha_resolucion), 1)
                            : null;
                        $est = $ticket->estado instanceof \BackedEnum ? $ticket->estado->value : (string) $ticket->estado;
                    @endphp
                    <tr>
                        <td><strong>#{{ $ticket->id_ticket }}</strong></td>
                        <td>{{ $ticket->fecha_creacion?->format('d/m/Y H:i') }}</td>
                        <td>{{ $cliente?->nombre_razon_social ?? 'No especificado' }}</td>
                        <td><span class="badge badge-azul">{{ strtoupper($ticket->tipo) }}</span></td>
                        <td>{{ $ticket->usuarioAsignado?->nombre_usuario ?? 'Sin asignar' }}</td>
                        <td>
                            @if ($est === 'resuelto' || $est === 'cerrado')
                                <span class="badge badge-verde">{{ strtoupper($est) }}</span>
                            @elseif ($est === 'en_proceso' || $est === 'asignado')
                                <span class="badge badge-azul">{{ strtoupper($est) }}</span>
                            @else
                                <span class="badge badge-amarillo">{{ strtoupper($est) }}</span>
                            @endif
                        </td>
                        <td>{{ $horas !== null ? $horas.' hs' : 'En curso' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">No se registraron tickets en el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- Pie de firmas y certificación --}}
    <footer class="firmas">
        <div class="linea-firma">
            <strong>Firma Responsable Contable / Finanzas</strong><br>
            Villafañe Wifi
        </div>
        <div class="linea-firma">
            <strong>Firma Auditor / Dirección</strong><br>
            Villafañe Wifi
        </div>
    </footer>
</div>

</body>
</html>
