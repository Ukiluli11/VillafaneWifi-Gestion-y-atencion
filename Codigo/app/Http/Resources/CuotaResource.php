<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuotaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cuota' => $this->id_cuota,
            'id_servicio' => $this->id_servicio,
            'id_pago' => $this->id_pago,
            'periodo' => $this->periodo,
            'monto' => $this->monto,
            'fecha_emision' => $this->fecha_emision->toDateString(),
            'fecha_vencimiento' => $this->fecha_vencimiento->toDateString(),
            'estado' => $this->estado->value,
        ];
    }
}
