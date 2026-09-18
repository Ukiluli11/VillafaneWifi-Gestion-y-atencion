@extends('layouts.panel')
@section('titulo', 'Clientes')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Personas y comercios</p><h1>Clientes</h1><p>Buscá una ficha, revisá sus servicios o consultá su cuenta corriente.</p></div>
    @if ($navegacionPermitida['gestionar_clientes'])<a class="boton" href="{{ route('clientes.create') }}">+ Nuevo cliente</a>@endif
</div>
<form class="buscador" method="GET" role="search">
    <input name="buscar" value="{{ request('buscar') }}" aria-label="Buscar clientes" placeholder="Buscar por nombre, documento, WhatsApp o localidad">
    <button>Buscar</button>
</form>
<div class="tabla-contenedor"><table><thead><tr><th>Cliente</th><th>Documento</th><th>WhatsApp</th><th>Localidad</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse ($clientes as $cliente)
<tr><td class="celda-principal"><strong>{{ $cliente->nombre_razon_social }}</strong><small>{{ ucfirst($cliente->tipo_cliente->value) }}</small></td><td>{{ $cliente->tipo_documento->value }} {{ $cliente->numero_documento }}</td><td>{{ $cliente->telefono_whatsapp ?: '—' }}</td><td>{{ $cliente->localidad_contacto ?: '—' }}</td><td><span class="estado {{ $cliente->estado->value }}">{{ ucfirst($cliente->estado->value) }}</span></td><td><a class="enlace-tabla" href="{{ route('clientes.show', $cliente) }}">Ver ficha →</a></td></tr>
@empty <tr><td class="vacio" colspan="6">No se encontraron clientes con ese criterio.</td></tr>@endforelse
</tbody></table></div>{{ $clientes->links() }}
@endsection
