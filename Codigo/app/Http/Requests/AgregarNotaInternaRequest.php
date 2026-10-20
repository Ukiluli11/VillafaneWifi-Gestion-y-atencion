<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgregarNotaInternaRequest extends FormRequest
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
            'contenido' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'El contenido de la nota interna es obligatorio.',
            'contenido.min' => 'La nota debe tener al menos 3 caracteres.',
            'contenido.max' => 'La nota no puede superar los 2000 caracteres.',
        ];
    }
}
