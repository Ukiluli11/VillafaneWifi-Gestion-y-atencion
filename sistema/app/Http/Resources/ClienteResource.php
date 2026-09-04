<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'tipo_documento' => $this->tipo_documento->value,
            'numero_documento' => $this->numero_documento,
            'nombre_razon_social' => $this->nombre_razon_social,
            'tipo_cliente' => $this->tipo_cliente->value,
            'calle_contacto' => $this->calle_contacto,
            'numero_contacto' => $this->numero_contacto,
            'localidad_contacto' => $this->localidad_contacto,
            'telefono_whatsapp' => $this->telefono_whatsapp,
            'estado' => $this->estado->value,
            'servicios' => ServicioResource::collection($this->whenLoaded('servicios')),
        ];
    }
}
