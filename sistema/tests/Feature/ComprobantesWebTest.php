<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/** Comprueba la bandeja de comprobantes informados por WhatsApp. */
class ComprobantesWebTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administracion_ve_y_filtra_los_comprobantes_pendientes(): void
    {
        $usuario = $this->crearEmpleadoAdministrativo();
        $cliente = Cliente::factory()->create(['nombre_razon_social' => 'Cliente con Transferencia']);
        $conversacion = Conversacion::factory()->for($cliente, 'cliente')->create();
        $mensaje = Mensaje::factory()->for($conversacion, 'conversacion')->create([
            'tipo' => 'imagen',
            'archivo_adjunto' => 'meta-media:archivo-1',
        ]);
        Comprobante::factory()->for($mensaje, 'mensaje')->create([
            'estado_validacion' => 'pendiente',
        ]);

        $this->actingAs($usuario)->get('/comprobantes?estado=pendiente')
            ->assertOk()
            ->assertSee('Cliente con Transferencia')
            ->assertSee('meta-media:archivo-1')
            ->assertSee('Pendiente');
    }

    public function test_soporte_no_puede_consultar_comprobantes(): void
    {
        $usuario = app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'usuario-soporte',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'soporte',
        ]);

        $this->actingAs($usuario)->get('/comprobantes')->assertForbidden();
    }

    /** Crea un empleado con las facultades de cobranza definidas por el sistema. */
    private function crearEmpleadoAdministrativo(): Usuario
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'usuario-administracion',
            'contrasena' => 'clave-segura',
            'tipo' => 'empleado',
            'area' => 'administracion',
        ]);
    }
}
