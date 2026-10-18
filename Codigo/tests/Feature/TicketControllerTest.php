<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Enums\EstadoTicket;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrador_puede_seguir_ticket_con_asignacion_notas_y_cierre(): void
    {
        $administrador = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-ticket',
            'contrasena' => 'clave-segura',
            'tipo' => 'administrador',
        ]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($administrador)
            ->get(route('tickets.index', ['buscar' => (string) $ticket->id_ticket]))
            ->assertOk()
            ->assertSee('#'.$ticket->id_ticket);
        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee($ticket->descripcion);

        $this->post(route('tickets.asignar', $ticket), ['id_usuario' => $administrador->id_usuario])
            ->assertRedirect();
        $this->assertSame(EstadoTicket::Asignado, $ticket->fresh()->estado);

        $this->post(route('tickets.notas.store', $ticket), ['contenido' => 'Se verificó el reclamo de prueba.'])
            ->assertRedirect();
        $this->assertDatabaseHas('nota_interna', [
            'id_ticket' => $ticket->id_ticket,
            'id_usuario' => $administrador->id_usuario,
            'contenido' => 'Se verificó el reclamo de prueba.',
        ]);
        $this->get(route('tickets.show', $ticket))->assertSee('Se verificó el reclamo de prueba.');

        $this->post(route('tickets.resolver', $ticket))->assertRedirect();
        $this->assertSame(EstadoTicket::Resuelto, $ticket->fresh()->estado);
        $this->post(route('tickets.cerrar', $ticket))->assertRedirect();
        $this->assertSame(EstadoTicket::Cerrado, $ticket->fresh()->estado);
        $this->post(route('tickets.reabrir', $ticket))->assertRedirect();
        $this->assertSame(EstadoTicket::Reabierto, $ticket->fresh()->estado);
    }

    public function test_operador_solo_puede_gestionar_el_ticket_que_tiene_asignado(): void
    {
        $operador = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'soporte-ticket',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);
        $otro = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'otro-soporte',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($operador)
            ->post(route('tickets.asignar', $ticket), ['id_usuario' => $otro->id_usuario])
            ->assertSessionHasErrors('id_usuario');
        $this->post(route('tickets.notas.store', $ticket), ['contenido' => 'Nota sin asignación'])
            ->assertForbidden();

        $this->post(route('tickets.asignar', $ticket), ['id_usuario' => $operador->id_usuario])
            ->assertRedirect();
        $this->post(route('tickets.notas.store', $ticket), ['contenido' => 'Nota de soporte'])
            ->assertRedirect();
        $this->assertDatabaseHas('nota_interna', ['id_ticket' => $ticket->id_ticket, 'contenido' => 'Nota de soporte']);

        $this->post(route('tickets.resolver', $ticket))->assertRedirect();
        $this->assertSame(EstadoTicket::Resuelto, $ticket->fresh()->estado);
    }
}
