<?php

namespace App\Http\Requests;

use App\Enums\EstadoPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPlanRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'velocidad' => ['required', 'string', 'max:50'],
            'precio_vigente' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'estado' => ['required', Rule::enum(EstadoPlan::class)],
        ];
    }
}
