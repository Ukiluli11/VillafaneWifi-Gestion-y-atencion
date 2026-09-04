@extends('layouts.panel')
@section('titulo', 'Servicios')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Conexiones contratadas</p><h1>Servicios</h1><p>Consultá el plan, la instalación y el estado de cada conexión.</p></div>
    @if ($navegacionPermitida['gestionar_servicios'])<a class="boton" href="{{ route('servicios.create') }}">+ Nuevo servicio</a>@endif
</div>
<div class="tabla-contenedor"><table><thead><tr><th>Servicio</th><th>Cliente</th><th>Plan</th><th>Instalación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($servicios as $servicio)<tr><td><strong>#{{ $servicio->id_servicio }}</strong></td><td class="celda-principal"><strong>{{ $servicio->cliente->nombre_razon_social }}</strong><small>{{ $servicio->ipv4 ?: 'Sin IPv4' }}</small></td><td>{{ $servicio->plan->nombre }}<br><small class="texto-suave">{{ $servicio->plan->velocidad }}</small></td><td>{{ $servicio->calle_instalacion }} {{ $servicio->numero_instalacion }}<br><small class="texto-suave">{{ $servicio->localidad_instalacion }}</small></td><td><span class="estado {{ $servicio->estado->value }}">{{ ucfirst($servicio->estado->value) }}</span></td><td class="acciones">@if ($navegacionPermitida['gestionar_servicios'])<a class="enlace-tabla" href="{{ route('servicios.edit',$servicio) }}">Editar</a>@if($servicio->estado->value==='activo')<form method="POST" action="{{ route('servicios.suspender',$servicio) }}" data-confirmar="¿Suspender este servicio?">@csrf<button class="enlace peligro-texto">Suspender</button></form>@elseif($servicio->estado->value==='suspendido')<form method="POST" action="{{ route('servicios.reactivar',$servicio) }}">@csrf<button class="enlace">Reactivar</button></form>@endif @endif</td></tr>
@empty<tr><td class="vacio" colspan="6">Todavía no hay servicios registrados.</td></tr>@endforelse
</tbody></table></div>{{ $servicios->links() }}
@endsection
