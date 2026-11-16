@extends('layouts.panel')
@section('titulo', 'Servicios')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Conexiones contratadas</p><h1>Servicios</h1><p>Consultá el plan, la instalación y el estado de cada conexión.</p></div>
    @if ($navegacionPermitida['gestionar_servicios'])<a class="boton" href="{{ route('servicios.create') }}">+ Nuevo servicio</a>@endif
</div>
<x-filtros-listado :ruta="route('servicios.index')" :busqueda="$busqueda" placeholder="N.º de servicio, cliente, dirección, IP o MAC">
    <label>Estado
        <select name="estado">
            <option value="">Todos los estados</option>
            @foreach (\App\Enums\EstadoServicio::cases() as $opcion)
                <option value="{{ $opcion->value }}" @selected($estado === $opcion)>{{ ucfirst($opcion->value) }}</option>
            @endforeach
        </select>
    </label>
    <label>Plan
        <select name="plan">
            <option value="">Todos los planes</option>
            @foreach ($planes as $plan)
                <option value="{{ $plan->id_plan }}" @selected($planSeleccionado === $plan->id_plan)>{{ $plan->nombre }}</option>
            @endforeach
        </select>
    </label>
</x-filtros-listado>
<div class="tabla-contenedor"><table><thead><tr><th>Servicio</th><th>Cliente</th><th>Plan</th><th>Instalación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($servicios as $servicio)<tr><td><strong>#{{ $servicio->id_servicio }}</strong></td><td class="celda-principal"><strong>{{ $servicio->cliente->nombre_razon_social }}</strong><small>{{ $servicio->ipv4 ?: 'Sin IPv4' }}</small></td><td>{{ $servicio->plan->nombre }}<br><small class="texto-suave">{{ $servicio->plan->velocidad }}</small></td><td>{{ $servicio->calle_instalacion }} {{ $servicio->numero_instalacion }}<br><small class="texto-suave">{{ $servicio->localidad_instalacion }}</small></td><td><span class="estado {{ $servicio->estado->value }}">{{ ucfirst($servicio->estado->value) }}</span></td><td class="acciones">@if ($navegacionPermitida['gestionar_servicios'])<a class="enlace-tabla" href="{{ route('servicios.edit',$servicio) }}">Editar</a>@if($servicio->estado->value==='activo')<form method="POST" action="{{ route('servicios.suspender',$servicio) }}" data-confirmar="¿Suspender este servicio?">@csrf<button class="enlace peligro-texto">Suspender</button></form>@elseif($servicio->estado->value==='suspendido')<form method="POST" action="{{ route('servicios.reactivar',$servicio) }}">@csrf<button class="enlace">Reactivar</button></form>@endif @if ($servicio->estado !== \App\Enums\EstadoServicio::Baja)<form method="POST" action="{{ route('servicios.destroy', $servicio) }}" data-confirmar="¿Dar de baja definitiva por mora?">@csrf @method('DELETE')<button class="enlace peligro-texto">Baja por mora</button></form>@endif @endif</td></tr>
@empty<tr><td class="vacio" colspan="6">No se encontraron servicios.</td></tr>@endforelse
</tbody></table></div>{{ $servicios->links() }}
@endsection
