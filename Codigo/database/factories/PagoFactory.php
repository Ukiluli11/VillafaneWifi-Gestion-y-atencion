<?php

namespace Database\Factories;

use App\Models\CuentaReceptora;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_comprobante' => null,
            'id_cuenta' => CuentaReceptora::factory(),
            'fecha' => now()->toDateString(),
            'monto_total' => '10000.00',
            'medio_pago' => 'transferencia',
        ];
    }
}
