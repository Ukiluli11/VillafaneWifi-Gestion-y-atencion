<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimularMensajeWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('services.whatsapp.modo_simulacion');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'numero_whatsapp' => ['required', 'string', 'regex:/^[0-9+ ()-]{8,25}$/'],
            'tipo' => ['required', Rule::in(['text', 'image', 'document'])],
            'contenido' => ['required', 'string', 'max:4096'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'numero_whatsapp.regex' => 'Ingresá un número de WhatsApp válido.',
            'tipo.in' => 'El tipo de mensaje simulado no es válido.',
        ];
    }
}
