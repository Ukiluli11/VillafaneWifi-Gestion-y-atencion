<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearTicketRequest extends FormRequest
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
            'id_servicio' => ['required', 'integer', 'exists:servicio,id_servicio'],
            'tipo' => ['required', 'string', 'in:tecnico,administrativo,instalacion,reclamo'],
            'descripcion' => ['required', 'string', 'min:5', 'max:2000'],
            'id_usuario_asignado' => ['nullable', 'integer', 'exists:usuario,id_usuario'],
            'nota_inicial' => ['nullable', 'string', 'max:2000'],
            'id_conversacion' => ['nullable', 'integer', 'exists:conversacion,id_conversacion'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_servicio.required' => 'Debe seleccionar el servicio del cliente afectado.',
            'id_servicio.exists' => 'El servicio seleccionado no es válido.',
            'tipo.required' => 'Debe indicar el tipo de ticket.',
            'tipo.in' => 'El tipo de ticket seleccionado no es válido.',
            'descripcion.required' => 'Debe ingresar una descripción detallada del problema o reclamo.',
            'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'id_usuario_asignado.exists' => 'El técnico seleccionado no existe.',
        ];
    }
}
