<?php

namespace App\Http\Requests;

use App\Dominio\FormatoDatosCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos de un mensaje ingresado en el simulador de WhatsApp.
 */
class SimularMensajeWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && filter_var(config('services.whatsapp.modo_simulacion'), FILTER_VALIDATE_BOOL);
    }

    protected function prepareForValidation(): void
    {
        $telefono = FormatoDatosCliente::telefono($this->input('telefono'));
        if ($telefono !== null) {
            $this->merge(['telefono' => $telefono]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'telefono' => ['required', 'string', 'regex:/\A[0-9]{8,20}\z/'],
            'tipo' => ['required', Rule::in(['texto', 'imagen', 'documento'])],
            'contenido' => ['nullable', 'string', 'max:4096', 'required_if:tipo,texto'],
        ];
    }
}
