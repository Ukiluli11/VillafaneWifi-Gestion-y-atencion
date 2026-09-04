<?php

namespace App\Http\Requests;

use App\Enums\EstadoCuentaReceptora;
use App\Enums\TipoCuentaReceptora;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCuentaReceptoraRequest extends FormRequest
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
            'tipo' => ['required', Rule::enum(TipoCuentaReceptora::class)],
            'identificador' => ['required', 'string', 'max:100'],
            'estado' => ['sometimes', Rule::enum(EstadoCuentaReceptora::class)],
        ];
    }
}
