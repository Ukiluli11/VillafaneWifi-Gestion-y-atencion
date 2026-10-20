<?php

namespace Tests\Feature;

use App\Dominio\NotificacionService;
use App\Dominio\ServicioUsuarios;
use App\Enums\AreaEmpleado;
use App\Enums\EstadoTicket;
use App\Enums\TipoEmisorMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.modo_simulacion' => false,
            'services.whatsapp.access_token' => 'token-prueba',
            'services.whatsapp.phone_number_id' => 'telefono-prueba',
            'services.whatsapp.destinatarios_permitidos' => '',
            'services.whatsapp.control_consumo_habilitado' => false,
            'services.tickets.notificar_resultado' => true,
        ]);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.ticket-prueba']]])]);
    }

    private function crearAdministrador(): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-soporte',
            'contrasena' => 'clave-segura-123',
            'tipo' => 'administrador',
            'nivel_acceso' => 'total',
        ]);
    }

    private function crearTecnicoSoporte(string $nombre = 'tecnico-juan'): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => $nombre,
            'contrasena' => 'clave-segura-123',
            'tipo' => 'empleado',
            'nivel_acceso' => 'operativo',
            'area' => AreaEmpleado::Soporte,
            'cargo' => 'Técnico de Redes',
            'turno' => 'mañana',
            'fecha_ingreso' => now()->subYear(),
        ]);
    }

    private function crearEntornoServicio(): array
    {
        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'Agustín Gómez',
            'numero_documento' => '38492019',
            'telefono_whatsapp' => '5493704123456',
        ]);

        $plan = Plan::create([
            'nombre' => 'Fibra 100 Megas',
            'velocidad' => 100,
            'precio_vigente' => 18000.00,
            'estado' => 'activo',
        ]);

        $servicio = Servicio::create([
            'id_cliente' => $cliente->id_cliente,
            'id_plan' => $plan->id_plan,
            'calle_instalacion' => 'Av. San Martín',
            'numero_instalacion' => '1420',
            'localidad_instalacion' => 'Villafañe',
            'dia_vencimiento' => 10,
            'fecha_alta' => now()->subMonths(3),
            'ipv4' => '192.168.10.45',
            'mac' => 'AA:BB:CC:DD:EE:FF',
            'estado' => 'activo',
        ]);

        return [$cliente, $servicio, $plan];
    }

    public function test_usuario_puede_acceder_a_la_bandeja_de_tickets(): void
    {
        $admin = $this->crearAdministrador();

        $respuesta = $this->actingAs($admin)->get(route('tickets.index'));

        $respuesta->assertOk();
        $respuesta->assertSee('Bandeja de Tickets');
        $respuesta->assertSee('Total Incidentes');
        $respuesta->assertSee('Pendientes');
    }

    public function test_filtros_de_fecha_funcionan_separados_y_rechazan_rangos_invalidos(): void
    {
        $admin = $this->crearAdministrador();
        $anterior = Ticket::factory()->create(['fecha_creacion' => '2026-10-01 10:00:00']);
        $reciente = Ticket::factory()->create(['fecha_creacion' => '2026-10-20 10:00:00']);
        $this->actingAs($admin)->get(route('tickets.index', ['fecha_hasta' => '2026-10-10']))
            ->assertOk()->assertSee($anterior->descripcion)->assertDontSee($reciente->descripcion);
        $this->get(route('tickets.index', ['fecha_desde' => '2026-10-10']))
            ->assertOk()->assertSee($reciente->descripcion)->assertDontSee($anterior->descripcion);
        $this->get(route('tickets.index', ['fecha_desde' => '2026-10-20', 'fecha_hasta' => '2026-10-01']))
            ->assertSessionHasErrors('fecha_hasta');
    }

    public function test_generar_ticket_manual_desde_panel(): void
    {
        $admin = $this->crearAdministrador();
        $tecnico = $this->crearTecnicoSoporte();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $this->actingAs($admin)->get(route('tickets.create'))->assertOk()->assertSee('Descripción del Problema');

        $datos = [
            'id_servicio' => $servicio->id_servicio,
            'tipo' => 'tecnico',
            'descripcion' => 'Router desconfigurado tras corte general de energía eléctrica.',
            'id_usuario_asignado' => $tecnico->id_usuario,
            'nota_inicial' => 'Se pactó visita técnica para las 15:00 hs.',
        ];

        $respuesta = $this->actingAs($admin)->post(route('tickets.store'), $datos);

        $respuesta->assertRedirect();

        $ticket = Ticket::where('id_servicio', $servicio->id_servicio)->first();
        $this->assertNotNull($ticket);
        $this->assertSame('Router desconfigurado tras corte general de energía eléctrica.', $ticket->descripcion);
        $this->assertEquals(EstadoTicket::Asignado, $ticket->estado);
        $this->assertEquals($tecnico->id_usuario, $ticket->id_usuario_asignado);

        // Verifica que se creó la nota interna inicial
        $this->assertCount(1, $ticket->notas);
        $this->assertStringContainsString('visita técnica', $ticket->notas->first()->contenido);
    }

    public function test_filtrar_tickets_por_estado_y_busqueda(): void
    {
        $admin = $this->crearAdministrador();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Problema con wifi en planta alta',
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::Pendiente,
            'fecha_creacion' => now(),
        ]);

        Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Solicitud de cambio de clave de acceso',
            'tipo' => 'administrativo',
            'estado' => EstadoTicket::Resuelto,
            'fecha_creacion' => now(),
            'fecha_resolucion' => now(),
        ]);

        $resPendientes = $this->actingAs($admin)->get(route('tickets.index', ['estado' => 'pendiente']));
        $resPendientes->assertOk();
        $resPendientes->assertSee('Problema con wifi en planta alta');
        $resPendientes->assertDontSee('Solicitud de cambio de clave de acceso');

        $resBusqueda = $this->actingAs($admin)->get(route('tickets.index', ['busqueda' => 'planta alta']));
        $resBusqueda->assertOk();
        $resBusqueda->assertSee('Problema con wifi en planta alta');
        $resBusqueda->assertDontSee('Solicitud de cambio de clave de acceso');
    }

    public function test_asignar_ticket_a_tecnico(): void
    {
        $admin = $this->crearAdministrador();
        $tecnico = $this->crearTecnicoSoporte('tecnico-marcos');
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $ticket = Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Sin señal óptica en la ONU.',
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::Pendiente,
            'fecha_creacion' => now(),
        ]);

        $respuesta = $this->actingAs($admin)->post(route('tickets.asignar', $ticket), [
            'id_usuario_asignado' => $tecnico->id_usuario,
        ]);

        $respuesta->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertEquals($tecnico->id_usuario, $ticket->id_usuario_asignado);
        $this->assertEquals(EstadoTicket::Asignado, $ticket->estado);
        $this->assertNotNull($ticket->fecha_asignacion);

        // Verifica que se asentó una nota de asignación
        $this->assertStringContainsString('tecnico-marcos', $ticket->notas->first()->contenido);
    }

    public function test_ciclo_de_vida_en_proceso_y_resolucion(): void
    {
        $tecnico = $this->crearTecnicoSoporte();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $ticket = Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'id_usuario_asignado' => $tecnico->id_usuario,
            'descripcion' => 'Reemplazo de patch cord dañado.',
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::Asignado,
            'fecha_creacion' => now(),
        ]);

        // 1. Poner en proceso
        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), [
            'estado' => 'en_proceso',
        ]);
        $ticket->refresh();
        $this->assertEquals(EstadoTicket::EnProceso, $ticket->estado);

        // 2. Resolver con detalle y notificación
        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), [
            'estado' => 'resuelto',
            'observacion' => 'Patch cord reemplazado exitosamente. Conectividad 100% restablecida.',
            'notificar_whatsapp' => 1,
        ]);
        $ticket->refresh();
        $this->assertEquals(EstadoTicket::Resuelto, $ticket->estado);
        $this->assertNotNull($ticket->fecha_resolucion);

        // 3. Cerrar definitivamente
        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), [
            'estado' => 'cerrado',
        ]);
        $ticket->refresh();
        $this->assertEquals(EstadoTicket::Cerrado, $ticket->estado);
    }

    public function test_reabrir_ticket_no_solucionado(): void
    {
        $admin = $this->crearAdministrador();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $ticket = Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Caída de enlace esporádica.',
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::Resuelto,
            'fecha_creacion' => now()->subDays(2),
            'fecha_resolucion' => now()->subDay(),
        ]);

        $respuesta = $this->actingAs($admin)->post(route('tickets.reabrir', $ticket), [
            'motivo' => 'El cliente reporta que el enlace volvió a caer a las 18:00 hs.',
        ]);

        $respuesta->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertEquals(EstadoTicket::Reabierto, $ticket->estado);
        $this->assertNull($ticket->fecha_resolucion);

        $this->assertTrue($ticket->notas()->where('contenido', 'like', '%volvió a caer%')->exists());
    }

    public function test_agregar_nota_interna_tecnica(): void
    {
        $tecnico = $this->crearTecnicoSoporte();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $ticket = Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Verificación de atenuación óptica en caja NAP.',
            'id_usuario_asignado' => $tecnico->id_usuario,
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::EnProceso,
            'fecha_creacion' => now(),
        ]);

        $respuesta = $this->actingAs($tecnico)->post(route('tickets.notas.store', $ticket), [
            'contenido' => 'Potencia en puerto 4 de NAP: -18.2 dBm. Dentro de los parámetros óptimos.',
        ]);

        $respuesta->assertRedirect(route('tickets.show', $ticket));

        $nota = $ticket->notas()->latest('id_nota')->first();
        $this->assertNotNull($nota);
        $this->assertSame($tecnico->id_usuario, $nota->id_usuario);
        $this->assertStringContainsString('-18.2 dBm', $nota->contenido);
    }

    public function test_visualizar_conversacion_de_whatsapp_en_detalle_del_ticket(): void
    {
        $admin = $this->crearAdministrador();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $conversacion = Conversacion::create([
            'id_cliente' => $cliente->id_cliente,
            'numero_whatsapp' => '5493704123456',
            'estado' => 'cerrada',
            'modo_atencion' => 'bot',
            'fecha_hora_inicio' => now()->subHour(),
        ]);

        Mensaje::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'tipo_emisor' => 'cliente',
            'tipo' => 'texto',
            'contenido' => 'Hola, no tengo internet desde las 10 de la mañana',
            'fecha_hora' => now()->subHour(),
            'estado_envio' => 'entregado',
        ]);

        $ticket = Ticket::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio,
            'descripcion' => 'Sin conectividad reportada por chat',
            'tipo' => 'tecnico',
            'estado' => EstadoTicket::Pendiente,
            'fecha_creacion' => now(),
        ]);

        $respuesta = $this->actingAs($admin)->get(route('tickets.show', $ticket));

        $respuesta->assertOk();
        $respuesta->assertSee('Historial de WhatsApp Vinculado');
        $respuesta->assertSee('no tengo internet desde las 10 de la mañana');
    }

    public function test_no_se_vincula_un_ticket_a_la_conversacion_de_otro_cliente(): void
    {
        $admin = $this->crearAdministrador();
        $servicio = Servicio::factory()->create();
        $conversacion = Conversacion::factory()->create(['id_cliente' => Cliente::factory()->create()->id_cliente]);

        $this->actingAs($admin)->post(route('tickets.store'), [
            'id_servicio' => $servicio->id_servicio, 'id_conversacion' => $conversacion->id_conversacion,
            'tipo' => 'tecnico', 'descripcion' => 'Sin conexión desde ayer.',
        ])->assertSessionHasErrors('id_conversacion');
        $this->assertDatabaseCount('ticket', 0);
    }

    public function test_un_tecnico_no_modifica_tickets_de_otro_responsable_ni_salta_estados(): void
    {
        $tecnico = $this->crearTecnicoSoporte();
        $otro = $this->crearTecnicoSoporte('otro-tecnico');
        $ticket = Ticket::factory()->create(['id_usuario_asignado' => $otro->id_usuario, 'estado' => EstadoTicket::Asignado]);

        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), ['estado' => 'resuelto'])->assertForbidden();
        $this->post(route('tickets.notas.store', $ticket), ['contenido' => 'Nota no autorizada'])->assertForbidden();
        $this->post(route('tickets.reabrir', $ticket), ['motivo' => 'Intento no autorizado'])->assertForbidden();
        $this->assertSame(EstadoTicket::Asignado, $ticket->fresh()->estado);
        $this->assertDatabaseCount('nota_interna', 0);

        $this->actingAs($otro)->post(route('tickets.estado', $ticket), ['estado' => 'cerrado'])->assertSessionHasErrors('estado');
        $this->assertSame(EstadoTicket::Asignado, $ticket->fresh()->estado);
    }

    public function test_asignacion_registra_al_autor_y_rechaza_responsables_ajenos_a_soporte(): void
    {
        $admin = $this->crearAdministrador();
        $tecnico = $this->crearTecnicoSoporte();
        $otro = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'cobranza-ticket', 'contrasena' => 'clave-segura', 'tipo' => 'empleado', 'area' => 'administracion',
        ]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->post(route('tickets.asignar', $ticket), ['id_usuario_asignado' => $otro->id_usuario])
            ->assertSessionHasErrors('id_usuario_asignado');
        $this->post(route('tickets.asignar', $ticket), ['id_usuario_asignado' => $tecnico->id_usuario])->assertRedirect();
        $this->assertSame($admin->id_usuario, $ticket->notas()->first()->id_usuario);
    }

    public function test_aviso_real_de_ticket_usa_la_ventana_y_no_expone_notas_internas(): void
    {
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        $entrante = Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'tipo_emisor' => TipoEmisorMensaje::Cliente, 'fecha_hora' => now()->subHour()]);
        $ticket = Ticket::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio, 'estado' => EstadoTicket::Asignado]);

        $this->actingAs($admin)->post(route('tickets.estado', $ticket), [
            'estado' => 'resuelto', 'observacion' => 'Clave interna confidencial', 'notificar_whatsapp' => 1,
        ])->assertRedirect();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['type'] === 'text'
            && str_contains($request['text']['body'], 'resuelto')
            && ! str_contains($request['text']['body'], 'Clave interna confidencial'));
        app(NotificacionService::class)->notificarNovedad($ticket);
        Http::assertSentCount(1);

        Http::fake();
        $entrante->update(['fecha_hora' => now()->subHours(25)]);
        $ticket->update(['estado' => EstadoTicket::Cerrado]);
        $resultado = app(NotificacionService::class)->notificarNovedad($ticket);
        $this->assertSame('pendiente', $resultado['estado']);
        Http::assertNothingSent();
    }

    public function test_limite_de_consumo_no_deshace_la_resolucion_del_ticket(): void
    {
        $admin = $this->crearAdministrador();
        $cliente = Cliente::factory()->create(['telefono_whatsapp' => '5493704123456']);
        $servicio = Servicio::factory()->create(['id_cliente' => $cliente->id_cliente]);
        $conversacion = Conversacion::factory()->create(['id_cliente' => $cliente->id_cliente, 'numero_whatsapp' => $cliente->telefono_whatsapp]);
        Mensaje::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'tipo_emisor' => TipoEmisorMensaje::Cliente, 'fecha_hora' => now()->subHour()]);
        $ticket = Ticket::factory()->create(['id_conversacion' => $conversacion->id_conversacion,
            'id_servicio' => $servicio->id_servicio, 'estado' => EstadoTicket::Asignado]);
        config(['services.whatsapp.control_consumo_habilitado' => true, 'services.whatsapp.limite_servicios_mes' => 0]);

        $this->actingAs($admin)->post(route('tickets.estado', $ticket), [
            'estado' => 'resuelto', 'notificar_whatsapp' => 1,
        ])->assertRedirect()->assertSessionHas('exito', fn ($texto) => str_contains($texto, 'permanece guardada'));
        $this->assertSame(EstadoTicket::Resuelto, $ticket->fresh()->estado);
        Http::assertNothingSent();
    }
}
