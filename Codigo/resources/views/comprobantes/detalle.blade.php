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

<div class="grilla-comprobante">
    
    <!-- Columna Izquierda: Datos del Comprobante y Archivo -->
    <div class="columna-comprobante">
        <section class="tarjeta">
            <h2>Datos del Comprobante</h2>
            <dl>
                <dt>Cliente titular</dt>
                <dd>
                    <strong>{{ $comprobante->cliente->nombre_razon_social }}</strong><br>
                    <small class="texto-suave">{{ $comprobante->cliente->tipo_documento->value }}: {{ $comprobante->cliente->numero_documento }}</small>
                </dd>

                <dt>WhatsApp de contacto</dt>
                <dd>{{ $comprobante->cliente->telefono_whatsapp ?: 'Sin teléfono registrado' }}</dd>

                <dt>Monto detectado</dt>
                <dd><strong class="monto-comprobante" style="font-size: 1.2rem;">{{ $comprobante->monto_ocr === null ? 'Sin monto detectado' : '$'.number_format((float) $comprobante->monto_ocr, 2, ',', '.') }}</strong></dd>

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
                <div class="separador-comprobante" style="margin-top: 1.25rem; padding-top: 1rem;">
                    <a class="boton secundario" href="{{ route('comprobantes.archivo', $comprobante) }}" style="width: 100%; text-align: center;">
                        📄 Abrir archivo adjunto / imagen original ↗
                    </a>
                </div>
            @endif
        </section>

        <section class="tarjeta">
            <h2>Lectura automática</h2>
            <p>{{ match ($comprobante->estado_ocr) {
                'desactivado' => 'La lectura automática no estaba habilitada al recibir este archivo. Completá los datos revisando el original.',
                'en_cola' => 'El archivo está esperando su lectura automática. Actualizá la página para consultar el resultado.',
                'procesando' => 'Se está leyendo el archivo.',
                'procesado' => 'Lectura terminada. Compará siempre los datos con el original antes de aprobar.',
                'fallido' => $comprobante->error_ocr ?: 'No se pudo leer el archivo. Podés completarlo manualmente.',
                default => 'Este comprobante todavía no tiene una lectura automática registrada.',
            } }}</p>
            <p class="texto-suave">En PDF se lee solamente la primera página. El archivo original completo se conserva para revisión.</p>
            @if ($comprobante->completitud_ocr !== null)
                <p>Campos recuperados: {{ (int) round((float) $comprobante->completitud_ocr * 100) }}%. Este indicador no confirma que la transferencia sea válida.</p>
            @endif
            @if ($comprobante->estaPendiente() && ($navegacionPermitida['gestionar_comprobantes'] ?? false) && ! in_array($comprobante->estado_ocr, ['en_cola', 'procesando'], true))
                <form method="POST" action="{{ route('comprobantes.ocr', $comprobante) }}">
                    @csrf
                    <button class="boton secundario" type="submit">Solicitar lectura automática</button>
                </form>
            @endif
        </section>

        @if (! $comprobante->estaPendiente())
            <section class="tarjeta">
                <h2>Aviso al cliente</h2>
                @if ($avisoResultado)
                    <p>Estado del aviso: <strong>{{ ucfirst($avisoResultado->estado_envio->value) }}</strong>.</p>
                    @if ($navegacionPermitida['consultar_conversaciones'] ?? false)
                        <a href="{{ route('conversaciones.show', $avisoResultado->id_conversacion) }}">Ver aviso en la conversación</a>
                    @endif
                @else
                    <p>No hay un envío registrado para esta decisión.</p>
                @endif
                    <p>{{ ! config('services.comprobantes.notificar_resultado')
                        ? 'Los avisos de comprobantes están desactivados. La decisión queda guardada aunque el aviso no se envíe.'
                        : (config('services.comprobantes.plantillas_fuera_ventana')
                            ? 'Dentro de las 24 horas se responde por texto. Fuera de esa ventana se utiliza la plantilla de resultado configurada; su envío puede generar un cargo de Meta.'
                            : 'Solo se envían respuestas dentro de las 24 horas desde el último mensaje del cliente. Fuera de esa ventana el aviso queda pendiente.') }}</p>
                @if ($navegacionPermitida['gestionar_comprobantes'] ?? false)
                    <form method="POST" action="{{ route('comprobantes.notificar', $comprobante) }}">
                        @csrf
                        <button class="boton secundario" type="submit">Consultar o reintentar aviso</button>
                    </form>
                @endif
            </section>
        @endif

        @if ($comprobante->estaAprobado())
            <section class="tarjeta comprobante-aprobado" style="border-left: 4px solid #16a34a;">
                <h2 class="titulo-aprobado">Pago Acreditado Exitosamente</h2>
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
                    <dt>Validado por</dt>
                    <dd><strong>{{ $comprobante->usuario?->nombre_usuario ?: 'Operador / Sistema' }}</strong> el {{ $comprobante->fecha_hora_validacion ? $comprobante->fecha_hora_validacion->format('d/m/Y H:i') : ($comprobante->updated_at ? $comprobante->updated_at->format('d/m/Y H:i') : '—') }}</dd>
                </dl>
            </section>
        @endif

        @if ($comprobante->estaRechazado())
            <section class="tarjeta comprobante-rechazado" style="border-left: 4px solid #dc2626;">
                <h2 class="titulo-rechazado">Comprobante Rechazado</h2>
                <p class="titulo-rechazado" style="margin: 0.5rem 0;"><strong>Motivo registrado:</strong></p>
                <blockquote class="motivo-comprobante" style="margin: 0; padding: 0.75rem 1rem; border-radius: 6px; border-left: 3px solid #dc2626; font-style: italic;">
                    "{{ $comprobante->motivo_rechazo }}"
                </blockquote>
                <p class="texto-suave" style="font-size: 0.85rem; margin-top: 0.5rem;">
                    Rechazado por <strong>{{ $comprobante->usuario?->nombre_usuario ?: 'Operador' }}</strong> el {{ $comprobante->fecha_hora_validacion ? $comprobante->fecha_hora_validacion->format('d/m/Y H:i') : ($comprobante->updated_at ? $comprobante->updated_at->format('d/m/Y H:i') : '—') }}. El envío del aviso se consulta por separado.
                </p>
            </section>
        @endif
    </div>

    <!-- Columna Derecha: Cuotas Impagas y Formularios de Conciliación -->
    <div class="columna-comprobante">
        
        <section class="tarjeta">
            <h2>Cuotas Pendientes de Cancelación</h2>
            @if ($cuotasImpagas->isEmpty())
                <p class="titulo-aprobado">El cliente no registra cuotas pendientes al día de hoy.</p>
            @else
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
                        @foreach ($cuotasImpagas as $cuota)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>

        @if ($comprobante->estaPendiente() && ($navegacionPermitida['gestionar_comprobantes'] ?? false))
            <!-- Formulario de Aprobación y Conciliación -->
            <section class="tarjeta" style="border-top: 3px solid #16a34a;">
                <h2 class="titulo-aprobado">Aprobar y Acreditar Pago</h2>
                <p class="texto-suave" style="font-size: 0.9rem;">
                    Al confirmar, se registrará el pago y se cancelarán cuotas completas, comenzando por las más antiguas. No se admiten pagos parciales ni saldos a favor. Verificá el ingreso en la cuenta receptora antes de aprobar; el OCR no comprueba movimientos bancarios.
                </p>

                <form method="POST" action="{{ route('comprobantes.aprobar', $comprobante) }}" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <div class="campo">
                        <label for="numero_operacion">Número de operación *</label>
                        <input id="numero_operacion" name="numero_operacion" maxlength="50" value="{{ old('numero_operacion', $comprobante->numero_operacion) }}" required>
                    </div>

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

                    <div class="grilla-dos-campos">
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
                        <input type="date" id="fecha" name="fecha" value="{{ old('fecha', $comprobante->fecha_ocr?->toDateString()) }}" required>
                    </div>

                    <button class="boton" type="submit" style="background: #16a34a; border-color: #16a34a; justify-content: center;">
                        ✓ Confirmar y Aprobar Pago
                    </button>
                </form>
            </section>

            <!-- Formulario de Rechazo -->
            <section class="tarjeta" style="border-top: 3px solid #dc2626;">
                <h2 class="titulo-rechazado">Rechazar Comprobante</h2>
                <p class="texto-suave" style="font-size: 0.9rem;">
                    Si el comprobante es ilegible, no coincide con la cuenta receptora o presenta anomalías, podés rechazarlo indicando el motivo.
                </p>

                <form method="POST" action="{{ route('comprobantes.rechazar', $comprobante) }}" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @csrf

                    <div class="campo">
                        <label for="motivo_rechazo">Motivo del Rechazo *</label>
                        <input type="text" id="motivo_rechazo" name="motivo_rechazo" value="{{ old('motivo_rechazo') }}" placeholder="Ej: Comprobante ilegible, no se aprecia fecha ni monto" required>
                        <small class="texto-suave">Se guarda el motivo y se intenta avisar según la configuración de WhatsApp. Podés consultar el resultado del envío después del rechazo.</small>
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
