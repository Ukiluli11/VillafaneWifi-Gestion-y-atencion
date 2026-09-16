<?php

namespace Database\Factories;

use App\Models\Comprobante;
use App\Models\Mensaje;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comprobante> */
class ComprobanteFactory extends Factory
{
    /** Define un comprobante pendiente apto para pruebas. */
    public function definition(): array
    {
        return [
            'id_mensaje' => Mensaje::factory(),
            'id_usuario' => null,
            'hash_archivo' => hash('sha256', fake()->unique()->uuid()),
            'fecha_recepcion' => now(),
            'numero_operacion' => null,
            'monto_ocr' => null,
            'fecha_ocr' => null,
            'confianza_ocr' => null,
            'estado_validacion' => 'pendiente',
            'motivo_rechazo' => null,
            'fecha_hora_validacion' => null,
        ];
    }
}
