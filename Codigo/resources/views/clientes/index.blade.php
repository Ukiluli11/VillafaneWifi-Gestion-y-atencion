@extends('layouts.panel')
@section('titulo', 'Clientes')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Personas y comercios</p><h1>Clientes</h1><p>Buscá una ficha, revisá sus servicios o consultá su cuenta corriente.</p></div>
    @if ($navegacionPermitida['gestionar_clientes'])<a class="boton" href="{{ route('clientes.create') }}">+ Nuevo cliente</a>@endif
</div>
<x-filtros-listado :ruta="route('clientes.index')" :busqueda="$busqueda" placeholder="Nombre, documento, WhatsApp o localidad">
    <label>Localidad
        <select name="localidad">
            <option value="">Todas las localidades</option>
            @if ($localidad !== '' && ! in_array($localidad, $localidades, true))
                <option value="{{ $localidad }}" selected>{{ $localidad }}</option>
            @endif
            @foreach ($localidades as $opcion)
                <option value="{{ $opcion }}" @selected($localidad === $opcion)>{{ $opcion }}</option>
            @endforeach
        </select>
    </label>
    <label>Estado
        <select name="estado">
            <option value="">Todos los estados</option>
            @foreach (\App\Enums\EstadoCliente::cases() as $opcion)
                <option value="{{ $opcion->value }}" @selected($estado === $opcion)>{{ ucfirst($opcion->value) }}</option>
            @endforeach
        </select>
    </label>
    <label>Tipo de cliente
        <select name="tipo">
            <option value="">Todos los tipos</option>
            @foreach (\App\Enums\TipoCliente::cases() as $opcion)
                <option value="{{ $opcion->value }}" @selected($tipo === $opcion)>{{ ucfirst($opcion->value) }}</option>
            @endforeach
        </select>
    </label>
    <label>WhatsApp
        <select name="whatsapp">
            <option value="">Todos los clientes</option>
            <option value="con_numero" @selected($conWhatsapp === true)>Con número</option>
            <option value="sin_numero" @selected($conWhatsapp === false)>Sin número</option>
        </select>
    </label>
</x-filtros-listado>
<div class="tabla-contenedor"><table><thead><tr><th>Cliente</th><th>Documento</th><th>WhatsApp</th><th>Localidad</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse ($clientes as $cliente)
<tr><td class="celda-principal"><strong>{{ $cliente->nombre_razon_social }}</strong><small>{{ ucfirst($cliente->tipo_cliente->value) }}</small></td><td>{{ $cliente->tipo_documento->value }} {{ $cliente->numero_documento }}</td><td>{{ $cliente->telefono_whatsapp ?: '—' }}</td><td>{{ $cliente->localidad_contacto ?: '—' }}</td><td><span class="estado {{ $cliente->estado->value }}">{{ ucfirst($cliente->estado->value) }}</span></td><td><a class="enlace-tabla" href="{{ route('clientes.show', $cliente) }}">Ver ficha →</a></td></tr>
@empty <tr><td class="vacio" colspan="6">No se encontraron clientes con ese criterio.</td></tr>@endforelse
</tbody></table></div>{{ $clientes->links() }}
@endsection
