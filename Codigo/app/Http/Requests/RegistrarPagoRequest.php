<?php

namespace App\Http\Requests;

use App\Enums\MedioPago;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el importe y los datos de referencia de un pago ingresado en el sistema.
 */
class RegistrarPagoRequest extends FormRequest
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
            'cuotas' => ['required', 'array', 'min:1'],
            'cuotas.*' => ['integer', 'min:1', 'distinct', 'exists:cuota,id_cuota'],
            'id_cuenta' => ['required', 'integer', 'min:1', 'exists:cuenta_receptora,id_cuenta'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.max(today()->toDateString(), today('America/Argentina/Buenos_Aires')->toDateString())],
            'medio_pago' => ['required', Rule::enum(MedioPago::class)],
        ];
    }
}
