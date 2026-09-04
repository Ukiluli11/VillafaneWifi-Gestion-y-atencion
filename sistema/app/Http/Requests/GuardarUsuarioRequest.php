<?php

namespace App\Http\Requests;

use App\Enums\AreaEmpleado;
use App\Enums\NivelAcceso;
use App\Enums\TurnoEmpleado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_usuario' => ['required', 'string', 'max:50', 'unique:usuario,nombre_usuario'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
            'tipo' => ['required', Rule::in(['administrador', 'empleado'])],
            'nivel_acceso' => ['nullable', Rule::enum(NivelAcceso::class)],
            'puede_gestionar_usuarios' => ['nullable', 'boolean'],
            'puede_configurar_planes' => ['nullable', 'boolean'],
            'area' => ['nullable', 'required_if:tipo,empleado', Rule::enum(AreaEmpleado::class)],
            'cargo' => ['nullable', 'string', 'max:100'],
            'turno' => ['nullable', Rule::enum(TurnoEmpleado::class)],
            'fecha_ingreso' => ['nullable', 'date'],
        ];
    }
}
