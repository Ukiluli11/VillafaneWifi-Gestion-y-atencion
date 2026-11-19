<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los campos necesarios para crear un plan comercial.
 */
class GuardarPlanRequest extends FormRequest
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
            'velocidad' => ['required', 'string', 'max:50', 'not_regex:~(?:\A|/|\s)[-−]\s*[0-9]~u'],
            'precio_vigente' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'decimal:0,2'],
        ];
    }
}
