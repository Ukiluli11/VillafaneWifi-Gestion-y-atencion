<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoCuota;
use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verifica que el panel aplique los permisos al mostrar y ejecutar operaciones.
 */
class PermisosPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.modo_simulacion' => true]);
        Http::preventStrayRequests();
    }

    private function crearUsuario(string $perfil): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'auditoria-'.$perfil,
            'contrasena' => 'clave-pruebas-segura',
            'tipo' => $perfil === 'administrador' ? 'administrador' : 'empleado',
            'area' => $perfil === 'administrador' ? null : $perfil,
        ]);
    }

    public static function perfiles(): array
    {
        return [
            'administrador' => ['administrador'],
            'cobranza' => ['administracion'],
            'soporte' => ['soporte'],
            'atencion' => ['atencion_cliente'],
        ];
    }

    #[DataProvider('perfiles')]
    public function test_las_pantallas_respetan_los_permisos_del_perfil(string $perfil): void
    {
        $usuario = $this->crearUsuario($perfil);
        $puedeGestionar = in_array($perfil, ['administrador', 'administracion'], true);
        $cliente = Cliente::factory()->create();
        $rutas = [
            'inicio' => true, 'clientes.index' => true, 'clientes.create' => $puedeGestionar,
            'planes.index' => $puedeGestionar, 'planes.create' => $puedeGestionar,
            'servicios.index' => $perfil !== 'atencion_cliente', 'servicios.create' => $puedeGestionar,
            'cuentas-receptoras.index' => $perfil !== 'soporte',
            'conversaciones.index' => true, 'tickets.index' => true, 'tickets.create' => true,
            'comprobantes.index' => $perfil !== 'soporte', 'comprobantes.create' => $puedeGestionar,
            'reportes.dashboard' => true, 'reportes.morosidad' => true,
            'reportes.ingresos' => true, 'reportes.tickets' => true, 'reportes.alertas' => true,
            'usuarios.index' => $perfil === 'administrador', 'cuenta.contrasena' => true,
        ];
        $this->actingAs($usuario);
        foreach ($rutas as $ruta => $permitida) {
            $this->get(route($ruta))->assertStatus($permitida ? 200 : 403);
        }
        $this->get(route('cuentas.show', $cliente))->assertStatus($perfil === 'soporte' ? 403 : 200);
        $this->get(route('clientes.show', $cliente))->assertOk();
        $this->get(route('reportes.exportar', ['tipo' => 'morosidad', 'formato' => 'csv']))
            ->assertStatus($puedeGestionar ? 200 : 403);
        Http::assertNothingSent();
    }

    #[DataProvider('perfiles')]
    public function test_la_api_exige_los_mismos_permisos_para_crear_clientes(string $perfil): void
    {
        $usuario = $this->crearUsuario($perfil);
        $this->withBasicAuth($usuario->nombre_usuario, 'clave-pruebas-segura')
            ->getJson('/api/v1/clientes')->assertOk();
        $respuesta = $this->postJson('/api/v1/clientes', [
            'tipo_documento' => 'DNI', 'numero_documento' => '34567890',
            'nombre_razon_social' => 'Cliente de prueba API', 'tipo_cliente' => 'particular',
        ]);
        if (in_array($perfil, ['administrador', 'administracion'], true)) {
            $respuesta->assertCreated();
            $this->assertDatabaseCount('cliente', 1);
        } else {
            $respuesta->assertForbidden();
            $this->assertDatabaseCount('cliente', 0);
        }
        $this->getJson('/api/v1/planes')->assertStatus(in_array($perfil, ['administrador', 'administracion'], true) ? 200 : 403);
    }

    #[DataProvider('perfiles')]
    public function test_solo_cobranza_y_administradores_pueden_disparar_recordatorios(string $perfil): void
    {
        $usuario = $this->crearUsuario($perfil);
        $cliente = Cliente::factory()->create(['recordatorios_whatsapp' => true]);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $cuota = Cuota::factory()->create(['id_servicio' => $servicio->id_servicio,
            'id_pago' => null, 'estado' => EstadoCuota::Vencida, 'fecha_vencimiento' => now()->subDay()]);
        config(['services.whatsapp.recordatorios_habilitados' => true]);
        $puedeEnviar = in_array($perfil, ['administrador', 'administracion'], true);
        $pagina = $this->actingAs($usuario)->get(route('reportes.alertas'))->assertOk();
        if ($puedeEnviar) {
            $pagina->assertSee('Enviar recordatorio');
            $this->post(route('reportes.alertas.notificar'), ['id_cuota' => $cuota->id_cuota])
                ->assertRedirect()->assertSessionHas('exito');
            $this->assertDatabaseCount('mensaje', 1);
        } else {
            $pagina->assertDontSee('Enviar recordatorio')->assertSee('Envío reservado a cobranza');
            $this->post(route('reportes.alertas.notificar'), ['id_cuota' => $cuota->id_cuota])->assertForbidden();
            $this->assertDatabaseCount('mensaje', 0);
        }
        Http::assertNothingSent();
    }

    public function test_personal_sin_permiso_no_puede_modificar_datos_financieros_por_url(): void
    {
        $cliente = Cliente::factory()->create(['recordatorios_whatsapp' => false]);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $cuenta = CuentaReceptora::factory()->create();
        $comprobante = Comprobante::create(['id_cliente' => $cliente->id_cliente,
            'hash_archivo' => hash('sha256', 'comprobante-permisos'), 'estado_validacion' => 'pendiente']);
        foreach (['soporte', 'atencion_cliente'] as $perfil) {
            $this->flushSession();
            $this->actingAs($this->crearUsuario($perfil));
            foreach ([
                ['post', 'comprobantes.aprobar', $comprobante],
                ['post', 'comprobantes.rechazar', $comprobante],
                ['post', 'comprobantes.ocr', $comprobante],
                ['post', 'comprobantes.notificar', $comprobante],
                ['post', 'cuotas.store', []], ['post', 'pagos.store', []],
                ['post', 'cuentas-receptoras.store', []],
                ['put', 'cuentas-receptoras.update', $cuenta],
                ['patch', 'cuentas-receptoras.estado', $cuenta],
                ['delete', 'cuentas-receptoras.destroy', $cuenta],
                ['patch', 'clientes.recordatorios', $cliente],
                ['put', 'clientes.update', $cliente], ['delete', 'clientes.destroy', $cliente],
                ['put', 'planes.update', $servicio->id_plan], ['delete', 'planes.eliminar', $servicio->id_plan],
                ['put', 'servicios.update', $servicio], ['delete', 'servicios.destroy', $servicio],
            ] as [$metodo, $ruta, $parametros]) {
                $this->{$metodo}(route($ruta, $parametros))->assertForbidden();
            }
        }
        $this->assertSame(EstadoComprobante::Pendiente, $comprobante->fresh()->estado_validacion);
        $this->assertFalse($cliente->fresh()->recordatorios_whatsapp);
        $this->assertDatabaseCount('pago', 0);
        $this->assertDatabaseCount('cuota', 0);
        Http::assertNothingSent();
    }

    public function test_ninguna_area_de_empleado_puede_convertirse_en_administrador(): void
    {
        foreach (['administracion', 'soporte', 'atencion_cliente'] as $perfil) {
            $this->flushSession();
            $usuario = $this->crearUsuario($perfil);
            $this->actingAs($usuario)->put(route('usuarios.update', $usuario), [
                'nombre_usuario' => $usuario->nombre_usuario,
                'tipo' => 'administrador', 'nivel_acceso' => 'total',
                'puede_gestionar_usuarios' => true,
            ])->assertForbidden();
            $this->post(route('usuarios.store'), [
                'nombre_usuario' => 'admin-intruso', 'contrasena' => 'clave-intruso', 'tipo' => 'administrador',
            ])->assertForbidden();
            $this->assertNull($usuario->fresh()->administrador);
            $this->assertSame($perfil, $usuario->empleado->area->value);
        }
        $this->assertDatabaseCount('administrador', 0);
    }

    public function test_otro_operador_no_puede_interferir_en_la_atencion_asignada(): void
    {
        $responsable = $this->crearUsuario('soporte');
        $otro = $this->crearUsuario('atencion_cliente');
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente,
            'numero_whatsapp' => $cliente->telefono_whatsapp,
            'id_usuario_atencion' => $responsable->id_usuario, 'estado' => 'en_atencion', 'modo_atencion' => 'humano']);
        $this->actingAs($otro)->post(route('conversaciones.tomar', $conversacion))
            ->assertSessionHasErrors('conversacion');
        $this->post(route('conversaciones.responder', $conversacion), ['contenido' => 'Respuesta ajena'])
            ->assertSessionHasErrors('conversacion');
        $this->post(route('conversaciones.cerrar', $conversacion))->assertSessionHasErrors('conversacion');
        $this->assertSame($responsable->id_usuario, $conversacion->fresh()->id_usuario_atencion);
        $this->assertSame('en_atencion', $conversacion->estado->value);
        $this->assertDatabaseCount('mensaje', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('perfiles')]
    public function test_los_enlaces_entre_conversacion_y_comprobante_respetan_el_area(string $perfil): void
    {
        $usuario = $this->crearUsuario($perfil);
        $cliente = Cliente::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente,
            'numero_whatsapp' => $cliente->telefono_whatsapp]);
        $comprobante = Comprobante::create(['id_cliente' => $cliente->id_cliente,
            'id_conversacion' => $conversacion->id_conversacion,
            'hash_archivo' => hash('sha256', 'enlace-conversacion-comprobante'),
            'estado_validacion' => 'rechazado', 'monto_ocr' => '100.00']);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'clave_idempotencia' => 'comprobante:rechazado:'.$comprobante->id_comprobante,
            'tipo_emisor' => 'sistema', 'estado_envio' => 'enviado']);
        $pantalla = $this->actingAs($usuario)->get(route('conversaciones.show', $conversacion))->assertOk();
        if ($perfil === 'soporte') {
            $pantalla->assertDontSee(route('comprobantes.show', $comprobante), false);
            $this->get(route('comprobantes.show', $comprobante))->assertForbidden();
        } else {
            $pantalla->assertSee(route('comprobantes.show', $comprobante), false);
            $this->get(route('comprobantes.show', $comprobante))->assertOk()
                ->assertSee(route('conversaciones.show', $conversacion), false);
        }
    }

    public function test_cobranza_puede_aprobar_y_queda_registrado_el_autor_del_pago(): void
    {
        config(['services.comprobantes.notificar_resultado' => false, 'services.transferencias.habilitadas' => false]);
        $usuario = $this->crearUsuario('administracion');
        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $cuota = Cuota::factory()->create(['id_servicio' => $servicio->id_servicio,
            'id_pago' => null, 'monto' => '100.00', 'estado' => EstadoCuota::Pendiente]);
        $cuenta = CuentaReceptora::factory()->create(['estado' => 'activa']);
        $comprobante = Comprobante::create(['id_cliente' => $cliente->id_cliente,
            'hash_archivo' => hash('sha256', 'pago-autorizado-cobranza'), 'estado_validacion' => 'pendiente',
            'numero_operacion' => 'DEMO-COBRANZA-001', 'monto_ocr' => '100.00', 'fecha_ocr' => now()->toDateString()]);
        $this->actingAs($usuario)->post(route('comprobantes.aprobar', $comprobante), [
            'id_cuenta' => $cuenta->id_cuenta, 'medio_pago' => 'mercado_pago',
            'monto' => '100.00', 'fecha' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(EstadoCuota::Pagada, $cuota->fresh()->estado);
        $this->assertSame($usuario->id_usuario, $comprobante->fresh()->id_usuario);
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado_validacion);
        $this->assertDatabaseCount('pago', 1);
        $this->post(route('usuarios.store'), [])->assertForbidden();
    }

    #[DataProvider('perfiles')]
    public function test_tomar_una_conversacion_respeta_el_area_y_el_orden_de_tickets(string $perfil): void
    {
        $usuario = $this->crearUsuario($perfil);
        $cliente = Cliente::factory()->create();
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente,
            'numero_whatsapp' => $cliente->telefono_whatsapp, 'estado' => 'escalada']);
        $resuelto = Ticket::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio, 'estado' => EstadoTicket::Resuelto]);
        $antiguo = Ticket::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio, 'fecha_creacion' => now()->subHour()]);
        $reciente = Ticket::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio]);
        $this->actingAs($usuario)->post(route('conversaciones.tomar', $conversacion))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($usuario->id_usuario, $conversacion->fresh()->id_usuario_atencion);
        $this->assertSame(EstadoTicket::Resuelto, $resuelto->fresh()->estado);
        $this->assertNull($reciente->fresh()->id_usuario_asignado);
        $debeAsignar = in_array($perfil, ['administrador', 'soporte'], true);
        $this->assertSame($debeAsignar ? $usuario->id_usuario : null, $antiguo->fresh()->id_usuario_asignado);
        $this->assertSame($debeAsignar ? EstadoTicket::Asignado : EstadoTicket::Pendiente, $antiguo->fresh()->estado);
        if ($debeAsignar) {
            $this->assertSame($usuario->id_usuario, $antiguo->notas()->sole()->id_usuario);
        }
        Http::assertNothingSent();
    }
}
