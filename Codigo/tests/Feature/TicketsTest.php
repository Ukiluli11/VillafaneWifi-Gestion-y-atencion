<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Enums\AreaEmpleado;
use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Empleado;
use App\Models\Mensaje;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TicketsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function crearAdministrador(): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-soporte',
            'contrasena'     => 'clave-segura-123',
            'tipo'           => 'administrador',
            'nivel_acceso'   => 'total',
        ]);
    }

    private function crearTecnicoSoporte(string $nombre = 'tecnico-juan'): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => $nombre,
            'contrasena'     => 'clave-segura-123',
            'tipo'           => 'empleado',
            'nivel_acceso'   => 'operativo',
            'area'           => AreaEmpleado::Soporte,
            'cargo'          => 'Técnico de Redes',
            'turno'          => 'mañana',
            'fecha_ingreso'  => now()->subYear(),
        ]);
    }

    private function crearEntornoServicio(): array
    {
        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'Agustín Gómez',
            'numero_documento'    => '38492019',
            'telefono_whatsapp'   => '5493704123456',
        ]);

        $plan = Plan::create([
            'nombre'         => 'Fibra 100 Megas',
            'velocidad'      => 100,
            'precio_vigente' => 18000.00,
            'estado'         => 'activo',
        ]);

        $servicio = Servicio::create([
            'id_cliente'            => $cliente->id_cliente,
            'id_plan'               => $plan->id_plan,
            'calle_instalacion'     => 'Av. San Martín',
            'numero_instalacion'    => '1420',
            'localidad_instalacion' => 'Villafañe',
            'dia_vencimiento'       => 10,
            'fecha_alta'            => now()->subMonths(3),
            'ipv4'                  => '192.168.10.45',
            'mac'                   => 'AA:BB:CC:DD:EE:FF',
            'estado'                => 'activo',
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

    public function test_generar_ticket_manual_desde_panel(): void
    {
        $admin = $this->crearAdministrador();
        $tecnico = $this->crearTecnicoSoporte();
        [$cliente, $servicio] = $this->crearEntornoServicio();

        $datos = [
            'id_servicio'         => $servicio->id_servicio,
            'tipo'                => 'tecnico',
            'descripcion'         => 'Router desconfigurado tras corte general de energía eléctrica.',
            'id_usuario_asignado' => $tecnico->id_usuario,
            'nota_inicial'        => 'Se pactó visita técnica para las 15:00 hs.',
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
            'id_servicio'    => $servicio->id_servicio,
            'descripcion'    => 'Problema con wifi en planta alta',
            'tipo'           => 'tecnico',
            'estado'         => EstadoTicket::Pendiente,
            'fecha_creacion' => now(),
        ]);

        Ticket::create([
            'id_servicio'      => $servicio->id_servicio,
            'descripcion'      => 'Solicitud de cambio de clave de acceso',
            'tipo'             => 'administrativo',
            'estado'           => EstadoTicket::Resuelto,
            'fecha_creacion'   => now(),
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
            'id_servicio'    => $servicio->id_servicio,
            'descripcion'    => 'Sin señal óptica en la ONU.',
            'tipo'           => 'tecnico',
            'estado'         => EstadoTicket::Pendiente,
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
            'id_servicio'         => $servicio->id_servicio,
            'id_usuario_asignado' => $tecnico->id_usuario,
            'descripcion'         => 'Reemplazo de patch cord dañado.',
            'tipo'                => 'tecnico',
            'estado'              => EstadoTicket::Asignado,
            'fecha_creacion'      => now(),
        ]);

        // 1. Poner en proceso
        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), [
            'estado' => 'en_proceso',
        ]);
        $ticket->refresh();
        $this->assertEquals(EstadoTicket::EnProceso, $ticket->estado);

        // 2. Resolver con detalle y notificación
        $this->actingAs($tecnico)->post(route('tickets.estado', $ticket), [
            'estado'             => 'resuelto',
            'observacion'        => 'Patch cord reemplazado exitosamente. Conectividad 100% restablecida.',
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
            'id_servicio'      => $servicio->id_servicio,
            'descripcion'      => 'Caída de enlace esporádica.',
            'tipo'             => 'tecnico',
            'estado'           => EstadoTicket::Resuelto,
            'fecha_creacion'   => now()->subDays(2),
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
            'id_servicio'    => $servicio->id_servicio,
            'descripcion'    => 'Verificación de atenuación óptica en caja NAP.',
            'tipo'           => 'tecnico',
            'estado'         => EstadoTicket::EnProceso,
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
            'id_cliente'        => $cliente->id_cliente,
            'numero_whatsapp'   => '5493704123456',
            'estado'            => 'cerrada',
            'modo_atencion'     => 'bot',
            'fecha_hora_inicio' => now()->subHour(),
        ]);

        Mensaje::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'tipo_emisor'     => 'cliente',
            'tipo'            => 'texto',
            'contenido'       => 'Hola, no tengo internet desde las 10 de la mañana',
            'fecha_hora'      => now()->subHour(),
            'estado_envio'    => 'entregado',
        ]);

        $ticket = Ticket::create([
            'id_conversacion' => $conversacion->id_conversacion,
            'id_servicio'     => $servicio->id_servicio,
            'descripcion'     => 'Sin conectividad reportada por chat',
            'tipo'            => 'tecnico',
            'estado'          => EstadoTicket::Pendiente,
            'fecha_creacion'  => now(),
        ]);

        $respuesta = $this->actingAs($admin)->get(route('tickets.show', $ticket));

        $respuesta->assertOk();
        $respuesta->assertSee('Historial de WhatsApp Vinculado');
        $respuesta->assertSee('no tengo internet desde las 10 de la mañana');
    }
}
