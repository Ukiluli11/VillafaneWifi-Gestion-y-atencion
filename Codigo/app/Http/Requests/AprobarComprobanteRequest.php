<?php

namespace App\Http\Requests;

use App\Enums\MedioPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Valida los datos necesarios para aprobar un comprobante de pago.
 */
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
            'id_cuenta' => ['required', 'integer', 'min:1', 'exists:cuenta_receptora,id_cuenta'],
            'medio_pago' => ['required', new Enum(MedioPago::class)],
            'monto' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'fecha' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.max(today()->toDateString(), today('America/Argentina/Buenos_Aires')->toDateString())],
            'numero_operacion' => ['nullable', 'string', 'max:50'],
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
