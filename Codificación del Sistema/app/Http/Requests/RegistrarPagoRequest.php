<?php

namespace App\Http\Requests;

use App\Enums\MedioPago;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'cuotas.*' => ['integer', 'distinct', 'exists:cuota,id_cuota'],
            'id_cuenta' => ['required', 'integer', 'exists:cuenta_receptora,id_cuenta'],
            'fecha' => ['required', 'date'],
            'medio_pago' => ['required', Rule::enum(MedioPago::class)],
        ];
    }
}
