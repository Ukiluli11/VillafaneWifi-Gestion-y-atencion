<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirComprobanteRequest extends FormRequest
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
            'id_cliente' => ['required', 'integer', 'exists:cliente,id_cliente'],
            'archivo' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
            'numero_operacion' => ['nullable', 'string', 'max:50'],
            'monto' => ['nullable', 'numeric', 'min:0.01'],
            'fecha' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_cliente.required' => 'Debe seleccionar un cliente.',
            'id_cliente.exists' => 'El cliente seleccionado no existe.',
            'archivo.required' => 'Debe adjuntar una imagen o PDF del comprobante.',
            'archivo.mimes' => 'El archivo debe ser una imagen (JPG, PNG) o PDF.',
            'archivo.max' => 'El archivo no puede superar los 10 MB.',
            'monto.numeric' => 'El monto debe ser un valor numérico válido.',
        ];
    }
}
