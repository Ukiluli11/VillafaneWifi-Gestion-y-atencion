{{-- Lista las cuentas habilitadas para recibir pagos y sus opciones de gestión. --}}
@extends('layouts.panel')
@section('titulo', 'Cuentas receptoras')
@section('contenido')
<div class="encabezado">
    <div><p class="sobrelinea">Cobranza</p><h1>Cuentas receptoras</h1></div>
</div>

@if ($navegacionPermitida['gestionar_cobranza'])
    <section class="tarjeta">
        <div class="tarjeta-cabecera">
            <div><h2>Nueva cuenta</h2><p>Agregá un destino habilitado para registrar cobros.</p></div>
        </div>
        <form class="formulario fila" method="POST" action="{{ route('cuentas-receptoras.store') }}">
            @csrf
            @include('cuentas-receptoras.campos', ['cuenta' => null])
            <button type="submit">Agregar cuenta</button>
        </form>
    </section>
@endif

<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Nombre</th><th>Tipo</th><th>Identificador</th><th>Estado</th>
                @if ($navegacionPermitida['gestionar_cobranza'])<th>Acciones</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($cuentas as $cuenta)
                <tr>
                    <td class="celda-principal"><strong>{{ $cuenta->nombre }}</strong><small>Cuenta de cobro</small></td>
                    <td>{{ ucfirst(str_replace('_', ' ', $cuenta->tipo->value)) }}</td>
                    <td>{{ $cuenta->identificador }}</td>
                    <td><span class="estado {{ $cuenta->estado->value }}">{{ ucfirst($cuenta->estado->value) }}</span></td>
                    @if ($navegacionPermitida['gestionar_cobranza'])
                        @php($activa = $cuenta->estado === \App\Enums\EstadoCuentaReceptora::Activa)
                        <td><div class="acciones">
                            <a class="enlace-tabla" href="{{ route('cuentas-receptoras.edit', $cuenta) }}">Editar</a>
                            <form method="POST" action="{{ route('cuentas-receptoras.estado', $cuenta) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="estado" value="{{ $activa ? 'inactiva' : 'activa' }}">
                                <button type="submit" class="enlace">{{ $activa ? 'Desactivar' : 'Reactivar' }}</button>
                            </form>
                            @if (! $cuenta->pagos_exists)
                                <form method="POST" action="{{ route('cuentas-receptoras.destroy', $cuenta) }}" data-confirmar="¿Eliminar esta cuenta receptora?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="enlace peligro-texto">Eliminar</button>
                                </form>
                            @endif
                        </div></td>
                    @endif
                </tr>
            @empty
                <tr><td class="vacio" colspan="{{ $navegacionPermitida['gestionar_cobranza'] ? 5 : 4 }}">No hay cuentas receptoras.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
