{{-- Lista los planes comerciales y las acciones disponibles. --}}
@extends('layouts.panel')
@section('titulo', 'Planes')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Catálogo comercial</p><h1>Planes de servicio</h1><p>Administrá velocidades, precios vigentes y disponibilidad.</p></div>
    @if ($navegacionPermitida['gestionar_planes'])<a class="boton" href="{{ route('planes.create') }}">+ Nuevo plan</a>@endif
</div>
<x-filtros-listado :ruta="route('planes.index')" :busqueda="$busqueda" placeholder="Nombre o velocidad">
    <label>Estado
        <select name="estado">
            <option value="">Todos los estados</option>
            @foreach (\App\Enums\EstadoPlan::cases() as $opcion)
                <option value="{{ $opcion->value }}" @selected($estado === $opcion)>{{ ucfirst($opcion->value) }}</option>
            @endforeach
        </select>
    </label>
    <label>Servicios asociados
        <select name="uso">
            <option value="">Todos los planes</option>
            <option value="con_servicios" @selected($uso === 'con_servicios')>Con servicios</option>
            <option value="sin_servicios" @selected($uso === 'sin_servicios')>Sin servicios</option>
        </select>
    </label>
</x-filtros-listado>

<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Plan</th><th>Velocidad</th><th>Precio vigente</th><th>Estado</th>
                @if ($navegacionPermitida['gestionar_planes'])<th>Acciones</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($planes as $plan)
                <tr>
                    <td class="celda-principal"><strong>{{ $plan->nombre }}</strong></td>
                    <td>{{ $plan->velocidad }}</td>
                    <td><strong>${{ number_format((float) $plan->precio_vigente, 2, ',', '.') }}</strong></td>
                    <td><span class="estado {{ $plan->estado->value }}">{{ ucfirst($plan->estado->value) }}</span></td>
                    @if ($navegacionPermitida['gestionar_planes'])
                        <td>
                            <div class="acciones">
                                <a class="enlace-tabla" href="{{ route('planes.edit', $plan) }}">Editar</a>
                                @if ($plan->estado === \App\Enums\EstadoPlan::Activo)
                                    <form method="POST" action="{{ route('planes.destroy', $plan) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="enlace">Desactivar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('planes.reactivar', $plan) }}">
                                        @csrf
                                        <button type="submit" class="enlace">Reactivar</button>
                                    </form>
                                @endif
                                @if (! $plan->servicios_exists)
                                    <form method="POST" action="{{ route('planes.eliminar', $plan) }}" data-confirmar="¿Eliminar este plan?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="enlace peligro-texto">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td class="vacio" colspan="{{ $navegacionPermitida['gestionar_planes'] ? 5 : 4 }}">No se encontraron planes.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $planes->links() }}
@endsection
