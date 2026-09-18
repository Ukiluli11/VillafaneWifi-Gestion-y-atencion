@extends('layouts.panel')
@section('titulo', 'Planes')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Catálogo comercial</p><h1>Planes de servicio</h1><p>Administrá velocidades, precios vigentes y disponibilidad.</p></div>
    @if ($navegacionPermitida['gestionar_planes'])<a class="boton" href="{{ route('planes.create') }}">+ Nuevo plan</a>@endif
</div>
<div class="tabla-contenedor"><table><thead><tr><th>Plan</th><th>Velocidad</th><th>Precio vigente</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($planes as $plan)<tr><td class="celda-principal"><strong>{{ $plan->nombre }}</strong><small>Plan de conectividad</small></td><td>{{ $plan->velocidad }}</td><td><strong>${{ number_format((float)$plan->precio_vigente,2,',','.') }}</strong></td><td><span class="estado {{ $plan->estado->value }}">{{ ucfirst($plan->estado->value) }}</span></td><td>@if ($navegacionPermitida['gestionar_planes'])<a class="enlace-tabla" href="{{ route('planes.edit', $plan) }}">Editar →</a>@endif</td></tr>
@empty<tr><td class="vacio" colspan="5">Todavía no hay planes registrados.</td></tr>@endforelse
</tbody></table></div>{{ $planes->links() }}
@endsection
