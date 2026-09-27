<?php

namespace Tests\Feature;

use App\Dominio\ServicioContrataciones;
use App\Dominio\ServicioFacturacion;
use App\Enums\EstadoCuota;
use App\Enums\MedioPago;
use App\Models\Cliente;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Plan;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiciosFacturacionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_contratacion_calcula_proximo_vencimiento_y_normaliza_mac(): void
    {
        $this->travelTo('2026-09-03');
        $cliente = Cliente::factory()->create();
        $plan = Plan::factory()->create();

        $servicio = app(ServicioContrataciones::class)->crear($cliente, [
            'id_plan' => $plan->id_plan,
            'calle_instalacion' => 'San Martín', 'numero_instalacion' => '45',
            'localidad_instalacion' => 'Villafañe', 'dia_vencimiento' => 10,
            'fecha_alta' => '2026-09-03', 'ipv4' => '192.168.1.20', 'mac' => 'aa-bb-cc-dd-ee-ff',
        ]);

        $this->assertSame('AA:BB:CC:DD:EE:FF', $servicio->mac);
        $this->assertSame('2026-09-10', $servicio->proximo_vencimiento->toDateString());
    }

    public function test_generar_cuota_conserva_precio_y_no_duplica_periodo(): void
    {
        $this->travelTo('2026-09-03');
        $servicio = Servicio::factory()->create([
            'fecha_alta' => '2026-08-15', 'dia_vencimiento' => 10,
        ]);
        $servicio->plan->update(['precio_vigente' => '12500.50']);

        $primera = app(ServicioFacturacion::class)->generarCuota($servicio, '2026-09');
        $segunda = app(ServicioFacturacion::class)->generarCuota($servicio, '2026-09');

        $this->assertSame($primera->id_cuota, $segunda->id_cuota);
        $this->assertSame('12500.50', $primera->monto);
        $this->assertSame(1, Cuota::count());
    }

    public function test_pago_cancela_varias_cuotas_del_mismo_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->for($cliente, 'cliente')->create();
        $cuotaUno = Cuota::factory()->for($servicio, 'servicio')->create(['periodo' => '2026-07', 'monto' => '10000.25']);
        $cuotaDos = Cuota::factory()->for($servicio, 'servicio')->create(['periodo' => '2026-08', 'monto' => '12500.50']);
        $cuenta = CuentaReceptora::factory()->create();

        $pago = app(ServicioFacturacion::class)->registrarPago(
            [$cuotaUno->id_cuota, $cuotaDos->id_cuota], $cuenta, MedioPago::Transferencia, '2026-09-03',
        );

        $this->assertSame('22500.75', $pago->monto_total);
        $this->assertSame(2, Cuota::where('estado', EstadoCuota::Pagada->value)->count());
        $this->assertDatabaseMissing('cuota', ['id_cuota' => $cuotaUno->id_cuota, 'id_pago' => null]);
    }

    public function test_pago_rechaza_cuotas_de_clientes_distintos(): void
    {
        $cuotaUno = Cuota::factory()->create(['periodo' => '2026-07']);
        $cuotaDos = Cuota::factory()->create(['periodo' => '2026-08']);
        $cuenta = CuentaReceptora::factory()->create();

        $this->expectException(ValidationException::class);

        app(ServicioFacturacion::class)->registrarPago(
            [$cuotaUno->id_cuota, $cuotaDos->id_cuota], $cuenta, MedioPago::Efectivo, '2026-09-03',
        );
    }
}
