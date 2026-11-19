<?php

namespace App\Http\Requests;

use App\Enums\AreaEmpleado;
use App\Enums\NivelAcceso;
use App\Enums\TurnoEmpleado;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos de acceso y de perfil de las cuentas gestionadas en el panel.
 */
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
        $usuario = $this->route('usuario');
        $nombreUnico = Rule::unique('usuario', 'nombre_usuario');
        if ($usuario instanceof Usuario) {
            $nombreUnico->ignore($usuario);
        }

        return [
            'nombre_usuario' => ['required', 'string', 'max:50', $nombreUnico],
            'contrasena' => [$usuario ? 'prohibited' : 'required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed'],
            'tipo' => ['required', Rule::in(['administrador', 'empleado'])],
            'nivel_acceso' => ['nullable', Rule::enum(NivelAcceso::class)],
            'puede_gestionar_usuarios' => ['nullable', 'boolean'],
            'puede_configurar_planes' => ['nullable', 'boolean'],
            'area' => ['exclude_unless:tipo,empleado', 'required', Rule::enum(AreaEmpleado::class)],
            'cargo' => ['nullable', 'string', 'max:100'],
            'turno' => ['nullable', Rule::enum(TurnoEmpleado::class)],
            'fecha_ingreso' => ['nullable', 'date'],
        ];
    }
}
