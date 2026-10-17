<?php

namespace App\Http\Requests;

use App\Enums\MedioPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AprobarComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_cuenta' => ['required', 'integer', 'exists:cuenta_receptora,id_cuenta'],
            'medio_pago' => ['required', new Enum(MedioPago::class)],
            'monto' => ['nullable', 'numeric', 'min:0.01'],
            'fecha' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_cuenta.required' => 'Debe seleccionar una cuenta receptora.',
            'id_cuenta.exists' => 'La cuenta receptora seleccionada no existe.',
            'medio_pago.required' => 'Debe indicar el medio de pago utilizado.',
            'monto.numeric' => 'El monto a acreditar debe ser numérico.',
        ];
    }
}
