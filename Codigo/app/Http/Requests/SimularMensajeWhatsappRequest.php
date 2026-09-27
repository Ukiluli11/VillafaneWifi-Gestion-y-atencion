<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimularMensajeWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && filter_var(config('services.whatsapp.modo_simulacion'), FILTER_VALIDATE_BOOL);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'tipo' => ['required', Rule::in(['texto', 'imagen', 'documento'])],
            'contenido' => ['nullable', 'string', 'max:4096', 'required_if:tipo,texto'],
        ];
    }
}
