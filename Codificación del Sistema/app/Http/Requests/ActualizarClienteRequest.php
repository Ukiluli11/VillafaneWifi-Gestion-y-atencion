<?php

namespace App\Http\Requests;

use App\Enums\EstadoCliente;
use App\Enums\TipoCliente;
use App\Enums\TipoDocumento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarClienteRequest extends FormRequest
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
        $cliente = $this->route('cliente');

        return [
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'numero_documento' => [
                'required', 'string', 'max:20', 'regex:/^\d+$/',
                Rule::unique('cliente')->where('tipo_documento', $this->input('tipo_documento'))->ignore($cliente?->id_cliente, 'id_cliente'),
            ],
            'nombre_razon_social' => ['required', 'string', 'max:150'],
            'tipo_cliente' => ['required', Rule::enum(TipoCliente::class)],
            'calle_contacto' => ['nullable', 'string', 'max:100'],
            'numero_contacto' => ['nullable', 'string', 'max:10'],
            'localidad_contacto' => ['nullable', 'string', 'max:100'],
            'telefono_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[+0-9()\-\s]+$/'],
            'estado' => ['required', Rule::enum(EstadoCliente::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'numero_documento' => preg_replace('/\D+/', '', (string) $this->input('numero_documento')),
            'telefono_whatsapp' => $this->input('telefono_whatsapp')
                ? preg_replace('/\D+/', '', (string) $this->input('telefono_whatsapp'))
                : null,
        ]);
    }
}
