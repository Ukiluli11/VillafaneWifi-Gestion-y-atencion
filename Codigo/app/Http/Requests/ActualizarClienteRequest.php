<?php

namespace App\Http\Requests;

use App\Dominio\FormatoDatosCliente;
use App\Enums\EstadoCliente;
use App\Enums\TipoCliente;
use App\Enums\TipoDocumento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos recibidos al editar la ficha de un cliente.
 */
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
                'required', 'string', 'max:20', 'regex:/\A[0-9]+\z/',
                Rule::unique('cliente')->where('tipo_documento', $this->input('tipo_documento'))->ignore($cliente?->id_cliente, 'id_cliente'),
            ],
            'nombre_razon_social' => ['required', 'string', 'max:150'],
            'tipo_cliente' => ['required', Rule::enum(TipoCliente::class)],
            'calle_contacto' => ['nullable', 'string', 'max:100'],
            'numero_contacto' => ['nullable', 'string', 'max:10', 'regex:'.FormatoDatosCliente::SIN_NUMERO_NEGATIVO],
            'localidad_contacto' => ['nullable', 'string', 'max:100'],
            'telefono_whatsapp' => ['nullable', 'string', 'regex:/\A[0-9]{8,20}\z/'],
            'estado' => ['required', Rule::enum(EstadoCliente::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['numero_documento' => 'documento', 'telefono_whatsapp' => 'telefono'] as $campo => $formato) {
            $valor = FormatoDatosCliente::{$formato}($this->input($campo));
            if ($valor !== null) {
                $this->merge([$campo => $valor]);
            }
        }
    }
}
