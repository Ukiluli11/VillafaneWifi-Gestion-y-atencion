<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_plan' => $this->id_plan,
            'nombre' => $this->nombre,
            'velocidad' => $this->velocidad,
            'precio_vigente' => $this->precio_vigente,
            'estado' => $this->estado->value,
        ];
    }
}
