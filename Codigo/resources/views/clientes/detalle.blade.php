@extends('layouts.panel')
@section('titulo', $cliente->nombre_razon_social)
@section('contenido')
<div class="encabezado"><div><p class="sobrelinea">Ficha del cliente</p><h1>{{ $cliente->nombre_razon_social }}</h1><p>{{ $cliente->tipo_documento->value }} {{ $cliente->numero_documento }} · {{ ucfirst($cliente->estado->value) }}</p></div><div class="acciones">@if ($navegacionPermitida['gestionar_clientes'])<a class="boton secundario" href="{{ route('clientes.edit', $cliente) }}">Editar</a>@endif @if ($navegacionPermitida['cobranza'])<a class="boton" href="{{ route('cuentas.show', $cliente) }}">Cuenta corriente</a>@endif</div></div>
<section class="tarjeta"><h2>Contacto</h2><dl><dt>WhatsApp</dt><dd>{{ $cliente->telefono_whatsapp ?: 'Sin informar' }}</dd><dt>Dirección</dt><dd>{{ trim("{$cliente->calle_contacto} {$cliente->numero_contacto}") ?: 'Sin informar' }}, {{ $cliente->localidad_contacto ?: 'sin localidad' }}</dd></dl></section>
<section class="tarjeta"><h2>Servicios contratados</h2><div class="tabla-contenedor"><table><thead><tr><th>Plan</th><th>Instalación</th><th>Vencimiento</th><th>Estado</th></tr></thead><tbody>@forelse($cliente->servicios as $servicio)<tr><td>{{ $servicio->plan->nombre }}</td><td>{{ $servicio->calle_instalacion }} {{ $servicio->numero_instalacion }}, {{ $servicio->localidad_instalacion }}</td><td>Día {{ $servicio->dia_vencimiento }}</td><td>{{ ucfirst($servicio->estado->value) }}</td></tr>@empty<tr><td colspan="4">No tiene servicios registrados.</td></tr>@endforelse</tbody></table></div></section>
<section class="tarjeta">
    <div class="tarjeta-cabecera">
        <div>
            <h2>Recordatorios de cuotas por WhatsApp</h2>
            <p>Incluye avisos de cuotas próximas a vencer y vencidas.</p>
        </div>
        <span class="estado {{ $cliente->recordatorios_whatsapp ? 'activa' : 'inactiva' }}">
            {{ $cliente->recordatorios_whatsapp ? 'Habilitados' : 'Desactivados' }}
        </span>
    </div>
    @if ($navegacionPermitida['gestionar_clientes'])
        @if ($cliente->recordatorios_whatsapp || ($cliente->telefono_whatsapp && $cliente->estado === \App\Enums\EstadoCliente::Activo))
            <form method="POST" action="{{ route('clientes.recordatorios', $cliente) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="habilitados" value="{{ $cliente->recordatorios_whatsapp ? '0' : '1' }}">
                <button type="submit" class="{{ $cliente->recordatorios_whatsapp ? 'secundario' : '' }}">
                    {{ $cliente->recordatorios_whatsapp ? 'Desactivar recordatorios' : 'Habilitar recordatorios' }}
                </button>
            </form>
        @else
            <p class="texto-suave">Para habilitar los recordatorios, el cliente debe estar activo y tener un número de WhatsApp registrado.</p>
        @endif
    @endif
    @if (! config('services.whatsapp.recordatorios_habilitados') || ! config('services.whatsapp.plantillas_reales_habilitadas') || config('services.whatsapp.modo_simulacion'))
        <p class="texto-suave">El envío real de recordatorios está desactivado en el sistema.</p>
    @endif
</section>
@if ($navegacionPermitida['gestionar_clientes'])<form method="POST" action="{{ route('clientes.destroy', $cliente) }}" data-confirmar="¿Dar de baja al cliente y sus servicios?">@csrf @method('DELETE')<button class="peligro">Dar de baja</button></form>@endif
@endsection
