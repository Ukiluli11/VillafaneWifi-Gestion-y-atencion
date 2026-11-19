{{-- Lista las cuentas de los usuarios internos y sus acciones de gestión. --}}
@extends('layouts.panel')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="encabezado"><div><h1>Usuarios y permisos</h1></div></div>
<section class="tarjeta">
    <h2>Crear usuario</h2>
    @include('usuarios.campos')
</section>
<section class="tarjeta">
    <form method="GET" action="{{ route('usuarios.index') }}" class="formulario">
        <label>Buscar usuario<input type="search" name="buscar" value="{{ $buscar }}"></label>
        <button type="submit">Buscar</button>
    </form>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Usuario</th><th>Tipo</th><th>Área / nivel</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->nombre_usuario }}</td>
                    <td>{{ $usuario->administrador ? 'Administrador' : 'Empleado' }}</td>
                    <td>{{ $usuario->administrador?->nivel_acceso?->value ?? $usuario->empleado?->area?->value ?? '—' }}</td>
                    <td>{{ ucfirst($usuario->estado->value) }}</td>
                    <td class="acciones">
                        <a class="enlace-tabla" href="{{ route('usuarios.edit', $usuario) }}">Editar</a>
                        @if ($usuario->estado === \App\Enums\EstadoUsuario::Inactivo)
                            <form method="POST" action="{{ route('usuarios.activar', $usuario) }}">
                                @csrf
                                <button type="submit" class="enlace">Activar</button>
                            </form>
                        @elseif (auth()->id() !== $usuario->id_usuario)
                            <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" data-confirmar="¿Desactivar este usuario?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="enlace peligro-texto">Desactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No se encontraron usuarios.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $usuarios->links() }}
</section>
@endsection
