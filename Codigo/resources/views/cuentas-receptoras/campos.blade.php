<label>Nombre
    <input name="nombre" maxlength="100" value="{{ old('nombre', $cuenta?->nombre) }}" required>
</label>
<label>Tipo
    <select name="tipo">
        @foreach (['banco' => 'Banco', 'mercado_pago' => 'Mercado Pago', 'uala' => 'Ualá', 'otro' => 'Otro'] as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected(old('tipo', $cuenta?->tipo->value) === $valor)>{{ $etiqueta }}</option>
        @endforeach
    </select>
</label>
<label>Identificador
    <input name="identificador" maxlength="100" value="{{ old('identificador', $cuenta?->identificador) }}" required>
</label>
