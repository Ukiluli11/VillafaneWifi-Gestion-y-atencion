<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'motivo_rechazo' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_rechazo.required' => 'Debe especificar el motivo del rechazo.',
            'motivo_rechazo.min' => 'El motivo debe tener al menos 3 caracteres.',
            'motivo_rechazo.max' => 'El motivo no puede exceder los 255 caracteres.',
        ];
    }
}
