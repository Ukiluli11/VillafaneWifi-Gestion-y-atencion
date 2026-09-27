@extends('layouts.panel')
@section('titulo', "Comprobante #{$comprobante->id_comprobante} · Conciliación")
@section('contenido')
<div class="encabezado">
    <div>
        <p class="sobrelinea">Auditoría y Conciliación de Pagos</p>
        <h1>Comprobante #{{ $comprobante->id_comprobante }}</h1>
        <p>Cliente: <strong>{{ $comprobante->cliente->nombre_razon_social }}</strong> — Estado: <span class="estado {{ $comprobante->estado_validacion->value }}">{{ ucfirst($comprobante->estado_validacion->value) }}</span></p>
    </div>
    <div class="acciones" style="display: flex; gap: 0.5rem;">
        <a class="boton secundario" href="{{ route('comprobantes.index') }}">← Volver a la bandeja</a>
        <a class="boton secundario" href="{{ route('cuentas.show', $comprobante->cliente) }}" target="_blank">Ver cuenta corriente ↗</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; align-items: start;">
    
    <!-- Columna Izquierda: Datos del Comprobante y Archivo -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <section class="tarjeta">
            <h2>Datos del Comprobante</h2>
            <dl>
                <dt>Cliente titular</dt>
                <dd>
                    <strong>{{ $comprobante->cliente->nombre_razon_social }}</strong><br>
                    <small style="color: #64748b;">{{ $comprobante->cliente->tipo_documento->value }}: {{ $comprobante->cliente->numero_documento }}</small>
                </dd>

                <dt>WhatsApp de contacto</dt>
                <dd>{{ $comprobante->cliente->telefono_whatsapp ?: 'Sin teléfono registrado' }}</dd>

                <dt>Monto detectado</dt>
                <dd><strong style="font-size: 1.2rem; color: #0f172a;">${{ number_format((float) ($comprobante->monto_ocr ?: 0), 2, ',', '.') }}</strong></dd>

                <dt>Fecha detectada</dt>
                <dd>{{ $comprobante->fecha_ocr ? $comprobante->fecha_ocr->format('d/m/Y') : 'Sin fecha detectada' }}</dd>

                <dt>N° de Operación</dt>
                <dd><code>{{ $comprobante->numero_operacion ?: 'Sin número detectado' }}</code></dd>

                <dt>Canal de origen</dt>
                <dd>{{ $comprobante->origen === \App\Enums\OrigenComprobante::Whatsapp ? 'Bot de WhatsApp' : 'Carga manual desde panel' }}</dd>

                <dt>Hash Antifraude (SHA-256)</dt>
                <dd style="word-break: break-all;"><small><code>{{ $comprobante->hash_archivo }}</code></small></dd>

                <dt>Fecha de recepción</dt>
                <dd>{{ $comprobante->created_at ? $comprobante->created_at->format('d/m/Y H:i') : '—' }}</dd>
            </dl>

            @if ($comprobante->ruta_archivo)
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <a class="boton secundario" href="{{ asset('storage/' . $comprobante->ruta_archivo) }}" target="_blank" style="width: 100%; text-align: center;">
                        📄 Abrir archivo adjunto / imagen original ↗
                    </a>
                </div>
            @endif
        </section>

        @if ($comprobante->estaAprobado())
            <section class="tarjeta" style="border-left: 4px solid #16a34a; background: #f0fdf4;">
                <h2 style="color: #15803d;">Pago Acreditado Exitosamente</h2>
                <p style="margin: 0.5rem 0;">Este comprobante fue conciliado y generó el <strong>Pago #{{ $comprobante->pago?->id_pago }}</strong>.</p>
                <dl>
                    <dt>Monto total acreditado</dt>
                    <dd><strong>${{ number_format((float) ($comprobante->pago?->monto_total ?: 0), 2, ',', '.') }}</strong></dd>
                    <dt>Cuenta receptora</dt>
                    <dd>{{ $comprobante->pago?->cuenta?->nombre }} ({{ ucfirst($comprobante->pago?->cuenta?->tipo->value ?? '') }})</dd>
                    <dt>Medio de pago</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $comprobante->pago?->medio_pago->value ?? '')) }}</dd>
                    <dt>Fecha efectiva</dt>
                    <dd>{{ $comprobante->pago?->fecha ? $comprobante->pago->fecha->format('d/m/Y') : '—' }}</dd>
                </dl>
            </section>
        @endif

        @if ($comprobante->estaRechazado())
            <section class="tarjeta" style="border-left: 4px solid #dc2626; background: #fef2f2;">
                <h2 style="color: #b91c1c;">Comprobante Rechazado</h2>
                <p style="margin: 0.5rem 0; color: #7f1d1d;"><strong>Motivo registrado:</strong></p>
                <blockquote style="margin: 0; padding: 0.75rem 1rem; background: #fff; border-radius: 6px; border-left: 3px solid #dc2626; font-style: italic;">
                    "{{ $comprobante->motivo_rechazo }}"
                </blockquote>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">Se emitió la notificación correspondiente al cliente informando la no validación.</p>
            </section>
        @endif
    </div>

    <!-- Columna Derecha: Cuotas Impagas y Formularios de Conciliación -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Listado de cuotas impagas del cliente (RF-21) -->
        <section class="tarjeta">
            <h2>Cuotas Pendientes de Cancelación</h2>
            <p style="font-size: 0.9rem; color: #64748b;">
                Las cuotas se cancelan en orden cronológico (más antiguas primero) según la regla de negocio del sistema (RF-21).
            </p>
            <div class="tabla-contenedor" style="margin-top: 0.75rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Vencimiento</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cuotasImpagas as $cuota)
                            <tr>
                                <td><strong>{{ $cuota->periodo }}</strong></td>
                                <td>{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                                <td>${{ number_format((float) $cuota->monto, 2, ',', '.') }}</td>
                                <td>
                                    <span class="estado {{ $cuota->estado->value }}">
                                        {{ ucfirst($cuota->estado->value) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #16a34a; font-weight: 500;">
                                    El cliente no registra cuotas pendientes al día de hoy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($comprobante->estaPendiente() && ($navegacionPermitida['gestionar_comprobantes'] ?? false))
            <!-- Formulario de Aprobación y Conciliación -->
            <section class="tarjeta" style="border-top: 3px solid #16a34a;">
                <h2 style="color: #15803d;">Aprobar y Acreditar Pago</h2>
                <p style="font-size: 0.9rem; color: #64748b;">
                    Al confirmar, se registrará el pago oficial, se imputará a las cuotas impagas y se notificará al cliente.
                </p>

                <form method="POST" action="{{ route('comprobantes.aprobar', $comprobante) }}" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <div class="campo">
                        <label for="id_cuenta">Cuenta Receptora de Cobranza *</label>
                        <select id="id_cuenta" name="id_cuenta" required>
                            <option value="">-- Seleccionar cuenta de acreditación --</option>
                            @foreach ($cuentasReceptoras as $cta)
                                <option value="{{ $cta->id_cuenta }}" {{ old('id_cuenta') == $cta->id_cuenta ? 'selected' : '' }}>
                                    {{ $cta->nombre }} ({{ ucfirst($cta->tipo->value) }} · {{ $cta->identificador }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="campo">
                            <label for="medio_pago">Medio de Pago *</label>
                            <select id="medio_pago" name="medio_pago" required>
                                @foreach ($mediosPago as $medio)
                                    <option value="{{ $medio->value }}" {{ old('medio_pago', 'mercado_pago') === $medio->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $medio->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="campo">
                            <label for="monto">Monto a Imputar ($) *</label>
                            <input type="number" step="0.01" id="monto" name="monto" value="{{ old('monto', $comprobante->monto_ocr) }}" required>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha de Acreditación *</label>
                        <input type="date" id="fecha" name="fecha" value="{{ old('fecha', $comprobante->fecha_ocr ? $comprobante->fecha_ocr->toDateString() : date('Y-m-d')) }}" required>
                    </div>

                    <button class="boton" type="submit" style="background: #16a34a; border-color: #16a34a; justify-content: center;">
                        ✓ Confirmar y Aprobar Pago
                    </button>
                </form>
            </section>

            <!-- Formulario de Rechazo -->
            <section class="tarjeta" style="border-top: 3px solid #dc2626;">
                <h2 style="color: #b91c1c;">Rechazar Comprobante</h2>
                <p style="font-size: 0.9rem; color: #64748b;">
                    Si el comprobante es ilegible, no coincide con la cuenta receptora o presenta anomalías, podés rechazarlo indicando el motivo.
                </p>

                <form method="POST" action="{{ route('comprobantes.rechazar', $comprobante) }}" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <div class="campo">
                        <label for="motivo_rechazo">Motivo del Rechazo *</label>
                        <input type="text" id="motivo_rechazo" name="motivo_rechazo" value="{{ old('motivo_rechazo') }}" placeholder="Ej: Comprobante ilegible, no se aprecia fecha ni monto" required>
                        <small style="color: #64748b;">Este texto será notificado al cliente para que pueda subsanar el envío.</small>
                    </div>

                    <button class="boton" type="submit" style="background: #dc2626; border-color: #dc2626; justify-content: center;">
                        ✕ Rechazar Comprobante
                    </button>
                </form>
            </section>
        @endif

    </div>
</div>
@endsection
