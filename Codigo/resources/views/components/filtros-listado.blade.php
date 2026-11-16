@props(['ruta', 'busqueda', 'placeholder'])

<form class="buscador filtros-listado" method="GET" action="{{ $ruta }}" role="search">
    <label class="filtro-busqueda">
        Buscar
        <input type="search" name="buscar" value="{{ $busqueda }}" placeholder="{{ $placeholder }}">
    </label>
    {{ $slot }}
    <div class="acciones filtros-acciones">
        <button type="submit">Aplicar filtros</button>
        <a class="boton secundario" href="{{ $ruta }}">Limpiar</a>
    </div>
</form>
