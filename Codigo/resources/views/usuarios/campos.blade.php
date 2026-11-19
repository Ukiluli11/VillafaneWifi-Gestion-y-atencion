{{-- Comparte los campos de cuenta, perfil y permisos entre formularios de usuario. --}}
@php
    $edicion = isset($usuario);
    $administrador = $usuario->administrador ?? null;
    $empleado = $usuario->empleado ?? null;
    $tipo = old('tipo', $administrador ? 'administrador' : 'empleado');
@endphp
<form method="POST" class="formulario grilla" action="{{ $edicion ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
    @csrf
    @if ($edicion)
        @method('PUT')
    @endif
    <label>Nombre de usuario<input name="nombre_usuario" value="{{ old('nombre_usuario', $usuario->nombre_usuario ?? '') }}" maxlength="50" required></label>
    <label>Tipo
        <select name="tipo" data-tipo-usuario>
            <option value="empleado" @selected($tipo === 'empleado')>Empleado</option>
            <option value="administrador" @selected($tipo === 'administrador')>Administrador</option>
        </select>
    </label>
    @unless ($edicion)
        <label>Contraseña<input type="password" name="contrasena" minlength="8" autocomplete="new-password" required></label>
        <label>Repetir contraseña<input type="password" name="contrasena_confirmation" autocomplete="new-password" required></label>
    @endunless
    <div data-campos-empleado>
        <label>Área
            <select name="area">
                <option value="administracion" @selected(old('area', $empleado?->area?->value) === 'administracion')>Administración</option>
                <option value="soporte" @selected(old('area', $empleado?->area?->value) === 'soporte')>Soporte</option>
                <option value="atencion_cliente" @selected(old('area', $empleado?->area?->value) === 'atencion_cliente')>Atención al cliente</option>
            </select>
        </label>
        <label>Cargo<input name="cargo" value="{{ old('cargo', $empleado?->cargo) }}"></label>
        <label>Turno
            <select name="turno">
                <option value="">Sin turno</option>
                @foreach (\App\Enums\TurnoEmpleado::cases() as $turno)
                    <option value="{{ $turno->value }}" @selected(old('turno', $empleado?->turno?->value) === $turno->value)>{{ ucfirst($turno->value) }}</option>
                @endforeach
            </select>
        </label>
        <label>Fecha de ingreso<input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', $empleado?->fecha_ingreso?->toDateString()) }}"></label>
    </div>
    <div data-campos-administrador>
        <label>Nivel de acceso
            <select name="nivel_acceso">
                <option value="total" @selected(old('nivel_acceso', $administrador?->nivel_acceso?->value) === 'total')>Total</option>
                <option value="restringido" @selected(old('nivel_acceso', $administrador?->nivel_acceso?->value) === 'restringido')>Restringido</option>
            </select>
        </label>
        <p class="texto-suave">El nivel restringido limita la gestión de usuarios y planes.</p>
        @foreach (['puede_gestionar_usuarios' => 'Gestionar usuarios', 'puede_configurar_planes' => 'Configurar planes'] as $permiso => $etiqueta)
            <input type="hidden" name="{{ $permiso }}" value="0">
            <label><input type="checkbox" name="{{ $permiso }}" value="1" @checked(old($permiso, $administrador?->{$permiso} ?? true))>{{ $etiqueta }}</label>
        @endforeach
    </div>
    <div class="acciones ancho">
        <button type="submit">{{ $edicion ? 'Guardar cambios' : 'Crear usuario' }}</button>
        @if ($edicion)
            <a href="{{ route('usuarios.index') }}">Volver al listado</a>
        @endif
    </div>
</form>
