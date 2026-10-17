<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderConversacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['contenido' => ['required', 'string', 'max:4096']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'contenido.required' => 'Escribí un mensaje antes de enviarlo.',
            'contenido.max' => 'El mensaje no puede superar los 4096 caracteres.',
        ];
    }
}
