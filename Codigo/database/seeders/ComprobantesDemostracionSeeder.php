<?php

namespace Database\Seeders;

use App\Enums\EstadoComprobante;
use App\Enums\OrigenComprobante;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Pago;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ComprobantesDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::query()->orderBy('id_cliente')->take(3)->get();
        if ($clientes->count() < 3) {
            return;
        }

        $cliente1 = $clientes[0];
        $cliente2 = $clientes[1];
        $cliente3 = $clientes[2];

        // 1. Comprobante Pendiente de Conciliación (Origen WhatsApp con datos detectados por OCR)
        Comprobante::firstOrCreate(
            ['hash_archivo' => hash('sha256', 'demo-comprobante-pendiente-mp-01')],
            [
                'id_cliente' => $cliente1->id_cliente,
                'numero_operacion' => '8294719284',
                'monto_ocr' => '16500.00',
                'fecha_ocr' => CarbonImmutable::today()->toDateString(),
                'ruta_archivo' => null,
                'estado_validacion' => EstadoComprobante::Pendiente,
                'origen' => OrigenComprobante::Whatsapp,
            ]
        );

        // 2. Comprobante Aprobado y Conciliado vinculado a un pago existente
        $pagoExistente = Pago::first();
        if ($pagoExistente) {
            $comprobanteAprobado = Comprobante::firstOrCreate(
                ['hash_archivo' => hash('sha256', 'demo-comprobante-aprobado-02')],
                [
                    'id_cliente' => $cliente2->id_cliente,
                    'id_pago' => $pagoExistente->id_pago,
                    'numero_operacion' => '7192840192',
                    'monto_ocr' => (string) $pagoExistente->monto_total,
                    'fecha_ocr' => $pagoExistente->fecha->toDateString(),
                    'ruta_archivo' => null,
                    'estado_validacion' => EstadoComprobante::Aprobado,
                    'origen' => OrigenComprobante::PanelManual,
                ]
            );

            if ($pagoExistente->id_comprobante === null) {
                $pagoExistente->update(['id_comprobante' => $comprobanteAprobado->id_comprobante]);
            }
        }

        // 3. Comprobante Rechazado con motivo registrado
        Comprobante::firstOrCreate(
            ['hash_archivo' => hash('sha256', 'demo-comprobante-rechazado-03')],
            [
                'id_cliente' => $cliente3->id_cliente,
                'numero_operacion' => '10293847',
                'monto_ocr' => '12000.00',
                'fecha_ocr' => CarbonImmutable::yesterday()->toDateString(),
                'ruta_archivo' => null,
                'estado_validacion' => EstadoComprobante::Rechazado,
                'motivo_rechazo' => 'Comprobante ilegible, no coincide el CBU de la cuenta receptora informada.',
                'origen' => OrigenComprobante::Whatsapp,
            ]
        );
    }
}
