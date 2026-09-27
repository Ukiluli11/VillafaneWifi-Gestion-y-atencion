<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pago' => $this->id_pago,
            'id_comprobante' => $this->id_comprobante,
            'id_cuenta' => $this->id_cuenta,
            'fecha' => $this->fecha->toDateString(),
            'monto_total' => $this->monto_total,
            'medio_pago' => $this->medio_pago->value,
            'cuotas' => CuotaResource::collection($this->whenLoaded('cuotas')),
        ];
    }
}
