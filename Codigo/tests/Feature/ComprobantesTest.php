<?php

namespace Tests\Feature;

use App\Dominio\OCRService;
use App\Dominio\ServicioFacturacion;
use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoCuota;
use App\Enums\MedioPago;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Plan;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprobantesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function crearAdministrador()
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-comprobantes',
            'contrasena' => 'clave-segura-123',
            'tipo' => 'administrador',
            'nivel_acceso' => 'total',
        ]);
    }

    public function test_administrador_accede_a_la_bandeja_de_conciliacion(): void
    {
        $admin = $this->crearAdministrador();

        $respuesta = $this->actingAs($admin)->get(route('comprobantes.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('Bandeja de Conciliación');
        $respuesta->assertSee('Pendientes de Conciliación');
    }

    public function test_subir_comprobante_calcula_hash_sha256_y_queda_en_estado_pendiente(): void
    {
        Storage::fake('public');
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create();

        $archivo = UploadedFile::fake()->create('comprobante_mp.jpg', 200, 'image/jpeg');

        $respuesta = $this->actingAs($admin)->post(route('comprobantes.store'), [
            'id_cliente' => $cliente->id_cliente,
            'archivo' => $archivo,
            'numero_operacion' => '12345678901',
            'monto' => '15000.00',
            'fecha' => '2026-09-27',
        ]);

        $comprobante = Comprobante::sole();
        $respuesta->assertRedirect(route('comprobantes.show', $comprobante));

        $this->assertSame(EstadoComprobante::Pendiente, $comprobante->estado_validacion);
        $this->assertSame('12345678901', $comprobante->numero_operacion);
        $this->assertSame('15000.00', (string) $comprobante->monto_ocr);
        $this->assertNotEmpty($comprobante->hash_archivo);
        $this->assertNotNull($comprobante->ruta_archivo);
        Storage::disk('public')->assertExists($comprobante->ruta_archivo);
    }

    public function test_antifraude_rechaza_comprobante_duplicado_por_hash_identico(): void
    {
        Storage::fake('public');
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create();

        // Creamos un archivo con contenido determinístico
        $contenido = 'COMPROBANTE_UNICO_TEST_123456';
        $archivo1 = UploadedFile::fake()->createWithContent('pago1.jpg', $contenido);
        $archivo2 = UploadedFile::fake()->createWithContent('pago_copia.jpg', $contenido);

        // Primer envío exitoso
        $this->actingAs($admin)->post(route('comprobantes.store'), [
            'id_cliente' => $cliente->id_cliente,
            'archivo' => $archivo1,
            'numero_operacion' => '88889999',
            'monto' => '10000.00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('comprobante', 1);

        // Segundo envío con el mismo archivo debe ser bloqueado por duplicación de hash
        $respuesta = $this->actingAs($admin)->post(route('comprobantes.store'), [
            'id_cliente' => $cliente->id_cliente,
            'archivo' => $archivo2,
            'numero_operacion' => '88889999',
            'monto' => '10000.00',
        ]);

        $respuesta->assertSessionHasErrors(['comprobante']);
        $this->assertDatabaseCount('comprobante', 1);
    }

    public function test_servicio_ocr_extrae_datos_correctamente(): void
    {
        $ocr = app(OCRService::class);

        $textoSimulado = "Comprobante de Transferencia Mercado Pago\n"
            . "Operación #9876543210\n"
            . "Monto: $ 18.500,00\n"
            . "Fecha: 25/09/2026\n"
            . "Destinatario: Villafañe Wifi";

        $datos = $ocr->procesarArchivo('', $textoSimulado);

        $this->assertSame('9876543210', $datos['numero_operacion']);
        $this->assertSame('18500.00', $datos['monto']);
        $this->assertSame('2026-09-25', $datos['fecha']);
    }

    public function test_aprobar_comprobante_genera_pago_e_imputa_cuotas_mas_antiguas_primero(): void
    {
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create();
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);

        $plan = Plan::factory()->create(['precio_vigente' => '10000.00']);
        $servicio = Servicio::factory()->create([
            'id_cliente' => $cliente->id_cliente,
            'id_plan' => $plan->id_plan,
        ]);

        // Creamos 2 cuotas impagas: una más antigua vencida y otra posterior
        $cuotaAntigua = Cuota::create([
            'id_servicio' => $servicio->id_servicio,
            'periodo' => '2026-07',
            'monto' => '10000.00',
            'fecha_emision' => '2026-07-01',
            'fecha_vencimiento' => '2026-07-10',
            'estado' => EstadoCuota::Vencida,
        ]);

        $cuotaReciente = Cuota::create([
            'id_servicio' => $servicio->id_servicio,
            'periodo' => '2026-08',
            'monto' => '10000.00',
            'fecha_emision' => '2026-08-01',
            'fecha_vencimiento' => '2026-08-10',
            'estado' => EstadoCuota::Pendiente,
        ]);

        // Registramos un comprobante pendiente por $10.000 (alcanza para 1 cuota)
        $comprobante = Comprobante::create([
            'id_cliente' => $cliente->id_cliente,
            'hash_archivo' => hash('sha256', 'pago-test-aprobacion'),
            'numero_operacion' => 'OP-999000',
            'monto_ocr' => '10000.00',
            'fecha_ocr' => '2026-09-27',
            'estado_validacion' => EstadoComprobante::Pendiente,
        ]);

        // Aprobamos el comprobante
        $respuesta = $this->actingAs($admin)->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => $cuenta->id_cuenta,
            'medio_pago' => MedioPago::MercadoPago->value,
            'monto' => '10000.00',
            'fecha' => '2026-09-27',
        ]);

        $respuesta->assertRedirect(route('comprobantes.show', $comprobante));
        $respuesta->assertSessionHas('exito');

        $comprobante->refresh();
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->estado_validacion);
        $this->assertNotNull($comprobante->id_pago);
        $this->assertSame($admin->id_usuario, $comprobante->id_usuario);
        $this->assertNotNull($comprobante->fecha_hora_validacion);

        // Verificamos que se canceló la cuota MÁS ANTIGUA primero (RF-21)
        $cuotaAntigua->refresh();
        $cuotaReciente->refresh();

        $this->assertSame(EstadoCuota::Pagada, $cuotaAntigua->estado);
        $this->assertSame($comprobante->id_pago, $cuotaAntigua->id_pago);

        // La más reciente sigue pendiente porque el pago cubrió solo una
        $this->assertSame(EstadoCuota::Pendiente, $cuotaReciente->estado);
        $this->assertNull($cuotaReciente->id_pago);
    }

    public function test_rechazar_comprobante_registra_motivo_y_no_imputa_pagos(): void
    {
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create();

        $comprobante = Comprobante::create([
            'id_cliente' => $cliente->id_cliente,
            'hash_archivo' => hash('sha256', 'pago-test-rechazo'),
            'numero_operacion' => 'OP-RECHAZO-01',
            'monto_ocr' => '5000.00',
            'fecha_ocr' => '2026-09-27',
            'estado_validacion' => EstadoComprobante::Pendiente,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('comprobantes.rechazar', $comprobante), [
            'motivo_rechazo' => 'Comprobante ilegible, no coincide el titular de la transferencia.',
        ]);

        $respuesta->assertRedirect(route('comprobantes.show', $comprobante));

        $comprobante->refresh();
        $this->assertSame(EstadoComprobante::Rechazado, $comprobante->estado_validacion);
        $this->assertSame('Comprobante ilegible, no coincide el titular de la transferencia.', $comprobante->motivo_rechazo);
        $this->assertSame($admin->id_usuario, $comprobante->id_usuario);
        $this->assertNotNull($comprobante->fecha_hora_validacion);
        $this->assertNull($comprobante->id_pago);
        $this->assertDatabaseCount('pago', 0);
    }

    public function test_no_se_puede_aprobar_o_rechazar_un_comprobante_ya_evaluado(): void
    {
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create();
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);

        $comprobante = Comprobante::create([
            'id_cliente' => $cliente->id_cliente,
            'hash_archivo' => hash('sha256', 'pago-ya-aprobado'),
            'numero_operacion' => 'OP-YA-APROBADO',
            'monto_ocr' => '5000.00',
            'fecha_ocr' => '2026-09-27',
            'estado_validacion' => EstadoComprobante::Aprobado, // Ya resuelto
        ]);

        $respuesta = $this->actingAs($admin)->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => $cuenta->id_cuenta,
            'medio_pago' => MedioPago::MercadoPago->value,
            'monto' => '5000.00',
            'fecha' => '2026-09-27',
        ]);

        $respuesta->assertSessionHasErrors(['comprobante']);
    }
}
