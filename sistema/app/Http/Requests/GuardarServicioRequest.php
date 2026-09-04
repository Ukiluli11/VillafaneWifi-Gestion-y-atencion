<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GuardarServicioRequest extends FormRequest
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
            'id_cliente' => ['required', 'integer', 'exists:cliente,id_cliente'],
            'id_plan' => ['required', 'integer', 'exists:plan,id_plan'],
            'calle_instalacion' => ['required', 'string', 'max:100'],
            'numero_instalacion' => ['nullable', 'string', 'max:10'],
            'localidad_instalacion' => ['required', 'string', 'max:100'],
            'dia_vencimiento' => ['required', 'integer', 'between:1,28'],
            'fecha_alta' => ['required', 'date'],
            'ipv4' => ['nullable', 'ipv4'],
            'mac' => ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
        ];
    }
}
