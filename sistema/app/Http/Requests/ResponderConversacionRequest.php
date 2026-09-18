<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el texto enviado por un usuario interno al cliente.
 */
class ResponderConversacionRequest extends FormRequest
{
    /** La autorización funcional se aplica mediante el middleware de acciones. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'contenido' => ['required', 'string', 'max:4096'],
        ];
    }

    /** Normaliza espacios exteriores antes de validar y guardar. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contenido' => trim((string) $this->input('contenido')),
        ]);
    }
}
