<?php

namespace Tests\Feature;

use App\Dominio\NotificacionService;
use App\Dominio\OCRService;
use App\Dominio\ServicioComprobantes;
use App\Dominio\ServicioRecepcionComprobantes;
use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoCuota;
use App\Enums\EstadoEnvioMensaje;
use App\Enums\MedioPago;
use App\Enums\TipoMensaje;
use App\Jobs\ProcesarOcrComprobante;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Modulo3ComprobantesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.control_consumo_habilitado' => false, 'services.transferencias.habilitadas' => false, 'services.ocr.habilitado' => false, 'services.ocr.tesseract_bin' => 'tesseract',
            'services.comprobantes.notificar_resultado' => false, 'services.whatsapp.modo_simulacion' => true]);
        Http::preventStrayRequests();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function admin(): Usuario
    {
        return app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'admin-m3', 'contrasena' => 'clave-m3-123', 'tipo' => 'administrador', 'nivel_acceso' => 'total']);
    }

    private function comprobante(?Cliente $cliente = null, array $atributos = []): Comprobante
    {
        return Comprobante::create(array_merge([
            'id_cliente' => ($cliente ?? Cliente::factory()->create())->id_cliente,
            'hash_archivo' => hash('sha256', fake()->uuid()), 'numero_operacion' => 'OP-123456789',
            'monto_ocr' => '18000.00', 'fecha_ocr' => today()->toDateString(),
            'estado_validacion' => EstadoComprobante::Pendiente,
        ], $atributos));
    }

    private function cuota(Cliente $cliente, string $monto = '18000.00'): Cuota
    {
        return Cuota::factory()->create(['id_servicio' => Servicio::factory()->create(['id_cliente' => $cliente->id_cliente])->id_servicio,
            'monto' => $monto, 'estado' => EstadoCuota::Pendiente, 'id_pago' => null]);
    }

    public function test_archivo_solo_se_descarga_con_sesion_y_permiso(): void
    {
        $contenido = 'archivo confidencial de prueba';
        $comprobante = $this->comprobante(atributos: ['ruta_archivo' => 'comprobantes/1/privado.pdf']);
        Storage::disk('local')->put($comprobante->ruta_archivo, $contenido);
        $this->get(route('comprobantes.archivo', $comprobante))->assertRedirect(route('sesion.crear'));
        $soporte = app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'soporte-m3', 'contrasena' => 'clave-soporte', 'tipo' => 'empleado', 'area' => 'soporte']);
        $this->actingAs($soporte)->get(route('comprobantes.archivo', $comprobante))->assertForbidden();
        $respuesta = $this->actingAs($this->admin())->get(route('comprobantes.archivo', $comprobante));
        $respuesta->assertOk()->assertDownload('privado.pdf');
        $this->assertSame($contenido, $respuesta->streamedContent());
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
    }

    public function test_carga_manual_es_privada_y_no_inventa_fecha_ni_monto(): void
    {
        $comprobante = app(ServicioComprobantes::class)->registrarComprobante(Cliente::factory()->create(), UploadedFile::fake()->create('pago.pdf', 20, 'application/pdf'));
        Storage::disk('local')->assertExists($comprobante->ruta_archivo);
        Storage::disk('public')->assertMissing($comprobante->ruta_archivo);
        $this->assertNull($comprobante->fecha_ocr);
        $this->assertNull($comprobante->monto_ocr);
        $this->assertSame('desactivado', $comprobante->estado_ocr);
    }

    public function test_traslado_historico_verifica_integridad_y_es_repetible(): void
    {
        $contenido = 'comprobante histórico';
        $comprobante = $this->comprobante(atributos: ['ruta_archivo' => 'comprobantes/historico.pdf', 'hash_archivo' => hash('sha256', $contenido)]);
        Storage::disk('public')->put($comprobante->ruta_archivo, $contenido);
        $this->artisan('comprobantes:privatizar')->assertSuccessful();
        Storage::disk('local')->assertExists($comprobante->ruta_archivo);
        Storage::disk('public')->assertMissing($comprobante->ruta_archivo);
        $this->assertSame($contenido, Storage::disk('local')->get($comprobante->ruta_archivo));
        $this->artisan('comprobantes:privatizar')->assertSuccessful();
    }

    public function test_traslado_no_borra_una_copia_si_falla_la_integridad(): void
    {
        $comprobante = $this->comprobante(atributos: ['ruta_archivo' => 'comprobantes/invalido.pdf']);
        Storage::disk('public')->put($comprobante->ruta_archivo, 'otro contenido');
        $this->artisan('comprobantes:privatizar')->assertFailed();
        Storage::disk('public')->assertExists($comprobante->ruta_archivo);
        Storage::disk('local')->assertMissing($comprobante->ruta_archivo);
    }

    public function test_ocr_normaliza_importes_y_rechaza_fechas_imposibles(): void
    {
        $ocr = app(OCRService::class);
        foreach (['$ 18.500,50', '$ 18500.50', 'Monto: 18500,50', '$ 18,500.50'] as $texto) {
            $this->assertSame('18500.50', $ocr->extraerMonto($texto), $texto);
        }
        $this->assertSame('18000.00', $ocr->extraerMonto('$ 18.000'));
        $this->assertNull($ocr->extraerFecha('Fecha: 31/02/2026'));
        $this->assertNull($ocr->extraerFecha('Fecha: 2026-13-20'));
        $this->assertSame('2026-10-20', $ocr->extraerFecha('Fecha: 20/10/2026'));
        $this->assertNull($ocr->extraerNumeroOperacion('DNI 12345678 CBU 1234567890123456789012'));
    }

    public function test_tesseract_lee_la_imagen_sin_enviar_datos_a_una_api(): void
    {
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('local')->put('comprobantes/test.png', $png);
        Process::fake(fn () => Process::result(output: "Operacion #9876543210\nMonto: $ 18.000,00\nFecha: 20/10/2026"));
        $datos = app(OCRService::class)->procesarArchivo(Storage::disk('local')->path('comprobantes/test.png'));
        $this->assertSame('18000.00', $datos['monto']);
        $this->assertSame('9876543210', $datos['numero_operacion']);
        Process::assertRan(fn ($proceso): bool => $proceso->command === ['tesseract', Storage::disk('local')->path('comprobantes/test.png'), 'stdout', '-l', 'spa+eng', '--oem', '1', '--psm', '6']);
        Http::assertNothingSent();
    }

    public function test_lee_formato_arq_con_coelsa_alfanumerico_mes_en_letras_y_monto_enviado(): void
    {
        $texto = "20 oct 2026 3:14a.m.\n--- Coelsa ID ---\ny\nH R7ZTEST1234ABCD5678\n"
            ."Estado de la transacción PENDIENTE\nCUIT 20123456789\nCBU/CVU 0000000000000000000001\n"
            ."Moneda ARS\nMonto enviado 100.00\nDescripción Enviado desde ARQ";
        $datos = app(OCRService::class)->procesarArchivo('texto de prueba', $texto);

        $this->assertSame('R7ZTEST1234ABCD5678', $datos['numero_operacion']);
        $this->assertSame('100.00', $datos['monto']);
        $this->assertSame('2026-10-20', $datos['fecha']);
        $this->assertSame('100.00', app(OCRService::class)->extraerMonto("Monto enviado\n100.00"));
        $this->assertSame('2026-10-20', app(OCRService::class)->extraerFecha('20 de octubre de 2026'));
        $this->assertNull(app(OCRService::class)->extraerFecha('31 de febrero de 2026'));
        $this->assertNull(app(OCRService::class)->extraerNumeroOperacion("Coelsa ID\nEstado PENDIENTE\nCUIT 20123456789"));
        $this->assertSame('ABC-123456XYZ', app(OCRService::class)->extraerNumeroOperacion('Número de operación: ABC-123456XYZ'));
        Http::assertNothingSent();
    }

    public function test_procesamiento_fallido_conserva_el_archivo_y_los_datos_manuales(): void
    {
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract']);
        Storage::disk('local')->put('comprobantes/test.pdf', '%PDF-1.4 archivo ficticio');
        $comprobante = $this->comprobante(atributos: ['ruta_archivo' => 'comprobantes/test.pdf', 'estado_ocr' => 'en_cola']);
        Process::fake(fn () => Process::result(exitCode: 1));
        (new ProcesarOcrComprobante($comprobante->id_comprobante))->handle(app(OCRService::class));
        $this->assertSame('fallido', $comprobante->fresh()->estado_ocr);
        $this->assertSame('18000.00', $comprobante->fresh()->monto_ocr);
        Storage::disk('local')->assertExists('comprobantes/test.pdf');
    }

    public function test_ocr_se_encola_y_no_bloquea_la_carga_manual(): void
    {
        Queue::fake();
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract']);
        $comprobante = app(ServicioComprobantes::class)->registrarComprobante(Cliente::factory()->create(), UploadedFile::fake()->create('pago.pdf', 20, 'application/pdf'));
        $this->assertSame('en_cola', $comprobante->estado_ocr);
        Queue::assertPushed(ProcesarOcrComprobante::class, fn ($job): bool => $job->idComprobante === $comprobante->id_comprobante && $job->connection === 'database');
        Http::assertNothingSent();
    }

    public function test_archivo_ilegible_no_inventa_datos_y_permite_conciliacion_manual(): void
    {
        config(['services.ocr.habilitado' => true]);
        $ruta = 'comprobantes/ilegible.png';
        Storage::disk('local')->put($ruta, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $cliente = Cliente::factory()->create();
        $cuota = $this->cuota($cliente);
        $comprobante = $this->comprobante($cliente, [
            'ruta_archivo' => $ruta, 'estado_ocr' => 'en_cola',
            'numero_operacion' => null, 'monto_ocr' => null, 'fecha_ocr' => null,
        ]);
        Process::fake(fn () => Process::result(output: ''));

        (new ProcesarOcrComprobante($comprobante->id_comprobante))->handle(app(OCRService::class));

        $comprobante->refresh();
        $this->assertSame('fallido', $comprobante->estado_ocr);
        $this->assertNull($comprobante->numero_operacion);
        $this->assertNull($comprobante->monto_ocr);
        $this->assertNull($comprobante->fecha_ocr);
        Storage::disk('local')->assertExists($ruta);
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('comprobantes.show', $comprobante))
            ->assertOk()->assertSee('Sin monto detectado')->assertSee('Sin fecha detectada');
        $this->actingAs($admin)->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => CuentaReceptora::factory()->create()->id_cuenta,
            'medio_pago' => 'transferencia', 'monto' => '18000.00',
            'fecha' => today()->toDateString(), 'numero_operacion' => 'MANUAL-6543210987',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado_validacion);
        $this->assertSame('MANUAL-6543210987', $comprobante->fresh()->numero_operacion);
        $this->assertSame(EstadoCuota::Pagada, $cuota->fresh()->estado);
        $this->assertDatabaseCount('pago', 1);
        Http::assertNothingSent();
    }

    public function test_importes_parciales_y_sobrantes_no_generan_pagos(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $this->cuota($cliente);
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        foreach (['10000.00', '20000.00'] as $monto) {
            $comprobante = $this->comprobante($cliente);
            $this->actingAs($admin)->post(route('comprobantes.aprobar', $comprobante), ['id_cuenta' => $cuenta->id_cuenta,
                'medio_pago' => MedioPago::Transferencia->value, 'monto' => $monto, 'fecha' => today()->toDateString()])->assertSessionHasErrors('monto');
            $this->assertSame(EstadoComprobante::Pendiente, $comprobante->fresh()->estado_validacion);
        }
        $this->assertDatabaseCount('pago', 0);
        $this->assertDatabaseHas('cuota', ['id_pago' => null]);
    }

    public function test_sin_cuotas_no_se_puede_acreditar_un_comprobante(): void
    {
        $comprobante = $this->comprobante();
        $this->actingAs($this->admin())->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => CuentaReceptora::factory()->create()->id_cuenta, 'medio_pago' => 'transferencia',
            'monto' => '18000.00', 'fecha' => today()->toDateString(),
        ])->assertSessionHasErrors('monto');
        $this->assertDatabaseCount('pago', 0);
    }

    public function test_destino_unico_bloquea_otra_cuenta_aunque_este_activa(): void
    {
        $cliente = Cliente::factory()->create();
        $cuota = $this->cuota($cliente, '10.00');
        $comprobante = $this->comprobante($cliente, ['monto_ocr' => '10.00']);
        $empresa = CuentaReceptora::factory()->create(['tipo' => 'mercado_pago']);
        $otra = CuentaReceptora::factory()->create();
        config(['services.transferencias.habilitadas' => true, 'services.transferencias.id_cuenta' => $empresa->id_cuenta]);
        $this->actingAs($this->admin());
        $this->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => $otra->id_cuenta, 'medio_pago' => 'mercado_pago',
            'monto' => '10.00', 'fecha' => today()->toDateString(),
        ])->assertSessionHasErrors('id_cuenta');
        $this->post(route('pagos.store'), ['cuotas' => [$cuota->id_cuota], 'id_cuenta' => $otra->id_cuenta,
            'medio_pago' => 'mercado_pago', 'fecha' => today()->toDateString(),
        ])->assertSessionHasErrors('id_cuenta');
        $this->assertDatabaseCount('pago', 0);
        $this->assertNull($cuota->fresh()->id_pago);
        $this->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => $empresa->id_cuenta, 'medio_pago' => 'mercado_pago',
            'monto' => '10.00', 'fecha' => today()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('pago', ['id_cuenta' => $empresa->id_cuenta, 'monto_total' => '10.00']);
        $this->assertSame(EstadoCuota::Pagada, $cuota->fresh()->estado);
    }

    public function test_operacion_repetida_con_archivo_distinto_no_se_acredita_dos_veces(): void
    {
        $cliente = Cliente::factory()->create();
        $this->cuota($cliente);
        $this->cuota($cliente);
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $admin = $this->admin();
        $primero = $this->comprobante($cliente);
        app(ServicioComprobantes::class)->conciliarYAprobar($primero, $cuenta, MedioPago::Transferencia, usuario: $admin);
        $segundo = $this->comprobante($cliente, ['numero_operacion' => 'op 123456789']);
        $this->actingAs($admin)->post(route('comprobantes.aprobar', $segundo), [
            'id_cuenta' => $cuenta->id_cuenta, 'medio_pago' => 'transferencia', 'monto' => '20000.00', 'fecha' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('comprobante');
        $this->assertDatabaseCount('pago', 1);
    }

    public function test_modelo_desactualizado_no_permite_aprobar_ni_rechazar_dos_veces(): void
    {
        $cliente = Cliente::factory()->create();
        $this->cuota($cliente);
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $admin = $this->admin();
        $comprobante = $this->comprobante($cliente);
        $copia = Comprobante::findOrFail($comprobante->id_comprobante);
        $servicio = app(ServicioComprobantes::class);
        $servicio->conciliarYAprobar($comprobante, $cuenta, MedioPago::Transferencia, usuario: $admin);
        try {
            $servicio->conciliarYAprobar($copia, $cuenta, MedioPago::Transferencia, usuario: $admin);
            $this->fail('Se permitió aprobar nuevamente.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('comprobante', $error->errors());
        }
        try {
            $servicio->rechazarComprobante($copia, 'No corresponde', $admin);
            $this->fail('Se permitió rechazar un pago aprobado.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('comprobante', $error->errors());
        }
        $this->assertDatabaseCount('pago', 1);
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado_validacion);
    }

    public function test_notificacion_real_se_registra_y_no_se_duplica(): void
    {
        config(['services.comprobantes.notificar_resultado' => true, 'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-ficticio', 'services.whatsapp.phone_number_id' => 'numero-ficticio',
            'services.whatsapp.graph_api_version' => 'v25.0', 'services.whatsapp.destinatarios_permitidos' => '']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.m3']]])]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subMinutes(5)]);
        $comprobante = $this->comprobante($cliente, ['estado_validacion' => EstadoComprobante::Rechazado]);
        $notificaciones = app(NotificacionService::class);
        $this->assertSame('enviado', $notificaciones->notificarPagoRechazado($cliente, $comprobante, 'Ilegible')['estado']);
        $notificaciones->notificarPagoRechazado($cliente, $comprobante, 'Ilegible');
        Http::assertSentCount(1);
        $this->assertDatabaseHas('mensaje', ['clave_idempotencia' => 'comprobante:rechazado:'.$comprobante->id_comprobante,
            'estado_envio' => EstadoEnvioMensaje::Enviado->value]);
    }

    public function test_fuera_de_ventana_no_se_envian_mensajes_ni_plantillas(): void
    {
        config(['services.comprobantes.notificar_resultado' => true]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subHours(25)]);
        $comprobante = $this->comprobante($cliente);
        $resultado = app(NotificacionService::class)->notificarPagoRechazado($cliente, $comprobante, 'Ilegible');
        $this->assertSame('pendiente', $resultado['estado']);
        Http::assertNothingSent();
    }

    public function test_pdf_se_convierte_y_procesa_sin_reemplazar_valores_manuales(): void
    {
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract']);
        $contenido = '%PDF-1.4 archivo de prueba';
        Storage::disk('local')->put('comprobantes/pago.pdf', $contenido);
        $comprobante = $this->comprobante(atributos: [
            'ruta_archivo' => 'comprobantes/pago.pdf', 'estado_ocr' => 'en_cola',
            'numero_operacion' => null, 'fecha_ocr' => null, 'monto_ocr' => '18000.00',
        ]);
        $imagenTemporal = null;
        Process::fake(function ($proceso) use (&$imagenTemporal) {
            if ($proceso->command[0] === 'pdftoppm') {
                $imagenTemporal = end($proceso->command).'.png';
                file_put_contents($imagenTemporal, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

                return Process::result();
            }

            return Process::result(output: "Operacion #9876543210\nMonto: $ 19.000,00\nFecha: 20/10/2026");
        });
        (new ProcesarOcrComprobante($comprobante->id_comprobante))->handle(app(OCRService::class));
        $comprobante->refresh();
        $this->assertSame('procesado', $comprobante->estado_ocr);
        $this->assertSame('18000.00', $comprobante->monto_ocr);
        $this->assertSame('9876543210', $comprobante->numero_operacion);
        $this->assertSame('2026-10-20', $comprobante->fecha_ocr->toDateString());
        $this->assertFileDoesNotExist($imagenTemporal);
        $this->assertDirectoryDoesNotExist(dirname($imagenTemporal));
        Process::assertRan(fn ($proceso): bool => $proceso->command[0] === 'pdftoppm' && array_slice($proceso->command, 1, 5) === ['-f', '1', '-l', '1', '-singlefile']);
        (new ProcesarOcrComprobante($comprobante->id_comprobante))->handle(app(OCRService::class));
        Process::assertRanTimes(fn () => true, 2);
        Http::assertNothingSent();
    }

    public function test_fuera_de_24_horas_envia_plantilla_habilitada_una_sola_vez(): void
    {
        config(['services.comprobantes.notificar_resultado' => true, 'services.comprobantes.plantillas_fuera_ventana' => true,
            'services.whatsapp.modo_simulacion' => false, 'services.whatsapp.access_token' => 'token-ficticio',
            'services.whatsapp.phone_number_id' => 'numero-ficticio', 'services.whatsapp.destinatarios_permitidos' => '']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.plantilla-rechazo']]])]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subHours(25)]);
        $c = $this->comprobante($cliente, ['estado_validacion' => EstadoComprobante::Rechazado]);
        $n = app(NotificacionService::class);

        $this->assertSame('enviado', $n->notificarPagoRechazado($cliente, $c, 'Archivo ilegible')['estado']);
        $n->notificarPagoRechazado($cliente, $c, 'Archivo ilegible');

        Http::assertSentCount(1);
        Http::assertSent(fn ($r): bool => $r['type'] === 'template'
            && $r['template']['name'] === 'demo_pago_rechazado_v1'
            && $r['template']['components'][0]['parameters'][2]['text'] === 'Archivo ilegible');
        $this->assertDatabaseCount('pago', 0);
    }

    public function test_plantillas_habilitadas_conservan_texto_libre_dentro_de_ventana(): void
    {
        config(['services.comprobantes.notificar_resultado' => true, 'services.comprobantes.plantillas_fuera_ventana' => true,
            'services.whatsapp.modo_simulacion' => false, 'services.whatsapp.access_token' => 'token-ficticio',
            'services.whatsapp.phone_number_id' => 'numero-ficticio', 'services.whatsapp.destinatarios_permitidos' => '']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.texto']]])]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subMinutes(5)]);
        $c = $this->comprobante($cliente, ['estado_validacion' => EstadoComprobante::Rechazado]);

        app(NotificacionService::class)->notificarPagoRechazado($cliente, $c, 'Archivo ilegible');

        Http::assertSent(fn ($r): bool => $r['type'] === 'text');
        Http::assertSentCount(1);
    }

    public function test_fallo_de_whatsapp_no_revierte_pago_y_el_reintento_no_duplica_pago(): void
    {
        config(['services.comprobantes.notificar_resultado' => true, 'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-ficticio', 'services.whatsapp.phone_number_id' => 'numero-ficticio',
            'services.whatsapp.graph_api_version' => 'v25.0', 'services.whatsapp.destinatarios_permitidos' => '']);
        Http::fakeSequence()->push(['error' => ['code' => 100]], 400)->push(['messages' => [['id' => 'wamid.reintento']]]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion, 'fecha_hora' => now()->subMinutes(5)]);
        $cuota = $this->cuota($cliente);
        $comprobante = $this->comprobante($cliente);
        $admin = $this->admin();
        $pago = app(ServicioComprobantes::class)->conciliarYAprobar($comprobante,
            CuentaReceptora::factory()->create(['estado' => 'activa']), MedioPago::Transferencia, usuario: $admin);
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado_validacion);
        $this->assertSame($pago->id_pago, $cuota->fresh()->id_pago);
        $this->assertDatabaseHas('mensaje', ['clave_idempotencia' => 'comprobante:aprobado:'.$comprobante->id_comprobante, 'estado_envio' => 'fallido']);
        $this->actingAs($admin)->post(route('comprobantes.notificar', $comprobante))->assertRedirect();
        $this->actingAs($admin)->post(route('comprobantes.notificar', $comprobante))->assertRedirect();
        $this->assertDatabaseCount('pago', 1);
        $this->assertDatabaseCount('mensaje', 2);
        $this->assertDatabaseHas('mensaje', ['id_mensaje_externo' => 'wamid.reintento', 'estado_envio' => 'enviado']);
        Http::assertSentCount(2);
    }

    public function test_solo_usuarios_de_gestion_pueden_reprocesar_y_notificar(): void
    {
        $consulta = app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'consulta-m3', 'contrasena' => 'clave-consulta', 'tipo' => 'empleado', 'area' => 'atencion_cliente']);
        $comprobante = $this->comprobante();
        $this->actingAs($consulta)->get(route('comprobantes.show', $comprobante))->assertOk();
        foreach (['ocr', 'notificar', 'aprobar', 'rechazar'] as $accion) {
            $this->actingAs($consulta)->post(route('comprobantes.'.$accion, $comprobante))->assertForbidden();
        }
        Http::assertNothingSent();
    }

    public function test_recepcion_whatsapp_guarda_pdf_privado_y_programa_ocr(): void
    {
        Queue::fake();
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract',
            'services.whatsapp.modo_simulacion' => false, 'services.whatsapp.access_token' => 'token-ficticio',
            'services.whatsapp.graph_api_version' => 'v25.0']);
        $contenido = '%PDF-1.4 comprobante ficticio';
        Http::fake([
            'graph.facebook.com/v25.0/media-m3' => Http::response(['url' => 'https://media.example.test/comprobante',
                'mime_type' => 'application/pdf', 'file_size' => strlen($contenido)]),
            'media.example.test/*' => Http::response($contenido, 200, ['Content-Type' => 'application/pdf']),
        ]);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'paso_actual' => 'esperando_comprobante']);
        $mensaje = Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'tipo' => TipoMensaje::Documento, 'contenido' => 'pago.pdf', 'archivo_adjunto' => 'meta://media-m3']);
        $respuesta = app(ServicioRecepcionComprobantes::class)->procesar($conversacion, $mensaje, $cliente);
        $this->assertStringContainsString('Recibimos', $respuesta);
        $comprobante = Comprobante::sole();
        $this->assertSame('en_cola', $comprobante->estado_ocr);
        $this->assertSame($contenido, Storage::disk('local')->get($comprobante->ruta_archivo));
        Storage::disk('public')->assertMissing($comprobante->ruta_archivo);
        Queue::assertPushed(ProcesarOcrComprobante::class);
        Http::assertSentCount(2);
    }

    public function test_error_de_cola_no_pierde_el_comprobante_recibido(): void
    {
        config(['services.ocr.habilitado' => true, 'services.ocr.tesseract_bin' => 'tesseract']);
        Queue::shouldReceive('connection')->with('database')->andThrow(new \RuntimeException('Cola no disponible'));
        $comprobante = app(ServicioComprobantes::class)->registrarComprobante(Cliente::factory()->create(), UploadedFile::fake()->create('pago.pdf', 20, 'application/pdf'));
        $this->assertSame('fallido', $comprobante->fresh()->estado_ocr);
        $this->assertSame(EstadoComprobante::Pendiente, $comprobante->fresh()->estado_validacion);
        Storage::disk('local')->assertExists($comprobante->ruta_archivo);
        $this->assertDatabaseCount('comprobante', 1);
    }
}
