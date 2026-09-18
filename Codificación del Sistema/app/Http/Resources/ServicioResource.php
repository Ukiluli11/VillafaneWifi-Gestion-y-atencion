<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_servicio' => $this->id_servicio,
            'id_plan' => $this->id_plan,
            'id_cliente' => $this->id_cliente,
            'calle_instalacion' => $this->calle_instalacion,
            'numero_instalacion' => $this->numero_instalacion,
            'localidad_instalacion' => $this->localidad_instalacion,
            'dia_vencimiento' => $this->dia_vencimiento,
            'proximo_vencimiento' => $this->proximo_vencimiento?->toDateString(),
            'fecha_alta' => $this->fecha_alta->toDateString(),
            'ipv4' => $this->ipv4,
            'mac' => $this->mac,
            'estado' => $this->estado->value,
            'plan' => new PlanResource($this->whenLoaded('plan')),
        ];
    }
}
