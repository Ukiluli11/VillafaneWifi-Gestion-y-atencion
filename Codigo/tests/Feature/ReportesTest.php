<?php

namespace Tests\Feature;

use App\Dominio\ServicioUsuarios;
use App\Enums\AreaEmpleado;
use App\Enums\EstadoCuota;
use App\Enums\EstadoServicio;
use App\Enums\EstadoTicket;
use App\Enums\MedioPago;
use App\Enums\TipoCuentaReceptora;
use App\Models\Cliente;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function crearAdmin()
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'admin-reportes',
            'contrasena' => 'clave-segura-123',
            'tipo' => 'administrador',
        ]);
    }

    private function crearEmpleado(AreaEmpleado $area = AreaEmpleado::Administracion)
    {
        return app(ServicioUsuarios::class)->crear([
            'nombre_usuario' => 'empleado-'.$area->value,
            'contrasena' => 'clave-segura-123',
            'tipo' => 'empleado',
            'area' => $area->value,
        ]);
    }

    private function crearServicio(int $idCliente, int $idPlan): Servicio
    {
        return Servicio::create([
            'id_cliente' => $idCliente,
            'id_plan' => $idPlan,
            'calle_instalacion' => 'Av. San Martín',
            'numero_instalacion' => '450',
            'localidad_instalacion' => 'Mayor Villafañe',
            'dia_vencimiento' => 10,
            'fecha_alta' => '2026-10-01',
            'proximo_vencimiento' => '2026-11-10',
            'estado' => EstadoServicio::Activo,
        ]);
    }

    public function test_acceso_al_dashboard_ejecutivo_autenticado(): void
    {
        $admin = $this->crearAdmin();

        $response = $this->actingAs($admin)->get(route('reportes.dashboard'));

        $response->assertOk();
        $response->assertViewIs('reportes.dashboard');
        $response->assertSee('Dashboard Ejecutivo y Métricas');
        $response->assertSee('CLIENTES ACTIVOS');
        $response->assertSee('MOROSIDAD EN CARTERA');
    }

    public function test_empleado_de_administracion_puede_ver_dashboard_y_reportes(): void
    {
        $empleado = $this->crearEmpleado(AreaEmpleado::Administracion);

        $response = $this->actingAs($empleado)->get(route('reportes.dashboard'));
        $response->assertOk();

        $responseMorosidad = $this->actingAs($empleado)->get(route('reportes.morosidad'));
        $responseMorosidad->assertOk();

        $responseIngresos = $this->actingAs($empleado)->get(route('reportes.ingresos'));
        $responseIngresos->assertOk();

        $responseTickets = $this->actingAs($empleado)->get(route('reportes.tickets'));
        $responseTickets->assertOk();
    }

    public function test_reporte_morosidad_filtra_clientes_al_dia_y_en_mora(): void
    {
        $admin = $this->crearAdmin();
        $plan = Plan::create(['nombre' => 'Fibra 50M', 'velocidad' => 50, 'precio_vigente' => 15000]);
        $cuenta = CuentaReceptora::create(['nombre' => 'Banco Formosa', 'tipo' => TipoCuentaReceptora::Banco, 'identificador' => '0123456789012345678901']);

        // Cliente 1: Al día (tiene un pago registrado)
        $clienteAlDia = Cliente::create(['nombre_razon_social' => 'Juan Al Día', 'tipo_documento' => 'DNI', 'numero_documento' => '30111222', 'telefono_whatsapp' => '5493704001122']);
        $servicio1 = $this->crearServicio($clienteAlDia->id_cliente, $plan->id_plan);
        $pago = Pago::create(['id_cuenta' => $cuenta->id_cuenta, 'fecha' => '2026-10-05', 'monto_total' => 15000, 'medio_pago' => MedioPago::Transferencia]);
        Cuota::create(['id_servicio' => $servicio1->id_servicio, 'periodo' => '2026-10', 'monto' => 15000, 'fecha_emision' => '2026-10-01', 'fecha_vencimiento' => '2026-10-10', 'estado' => EstadoCuota::Pagada, 'id_pago' => $pago->id_pago]);

        // Cliente 2: En mora (cuota vencida sin pago)
        $clienteEnMora = Cliente::create(['nombre_razon_social' => 'Carlos Moroso', 'tipo_documento' => 'DNI', 'numero_documento' => '30333444', 'telefono_whatsapp' => '5493704003344']);
        $servicio2 = $this->crearServicio($clienteEnMora->id_cliente, $plan->id_plan);
        Cuota::create(['id_servicio' => $servicio2->id_servicio, 'periodo' => '2026-09', 'monto' => 15000, 'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-10', 'estado' => EstadoCuota::Vencida, 'id_pago' => null]);

        // Consulta filtrando en mora
        $responseMora = $this->actingAs($admin)->get(route('reportes.morosidad', ['estado_financiero' => 'en_mora']));
        $responseMora->assertOk();
        $responseMora->assertSee('Carlos Moroso');
        $responseMora->assertDontSee('Juan Al Día');

        // Consulta filtrando al día
        $responseAlDia = $this->actingAs($admin)->get(route('reportes.morosidad', ['estado_financiero' => 'al_dia']));
        $responseAlDia->assertOk();
        $responseAlDia->assertSee('Juan Al Día');
        $responseAlDia->assertDontSee('Carlos Moroso');
    }

    public function test_reporte_ingresos_filtra_por_rango_de_fechas(): void
    {
        $admin = $this->crearAdmin();
        $cuenta = CuentaReceptora::create(['nombre' => 'Banco Formosa', 'tipo' => TipoCuentaReceptora::Banco, 'identificador' => '0123456789012345678901']);

        // Pago en Octubre 2026
        Pago::create(['id_cuenta' => $cuenta->id_cuenta, 'fecha' => '2026-10-15', 'monto_total' => 15000, 'medio_pago' => MedioPago::Transferencia]);
        // Pago en Septiembre 2026
        Pago::create(['id_cuenta' => $cuenta->id_cuenta, 'fecha' => '2026-09-15', 'monto_total' => 12000, 'medio_pago' => MedioPago::MercadoPago]);

        // Filtrar octubre
        $response = $this->actingAs($admin)->get(route('reportes.ingresos', [
            'fecha_desde' => '2026-10-01',
            'fecha_hasta' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertSee('15.000,00');
        $response->assertDontSee('12.000,00');
    }

    public function test_reporte_tickets_calcula_metricas_y_mttr(): void
    {
        $admin = $this->crearAdmin();
        $plan = Plan::create(['nombre' => 'Plan Básico', 'velocidad' => 20, 'precio_vigente' => 10000]);
        $cliente = Cliente::create(['nombre_razon_social' => 'Abelardo Reclamos', 'tipo_documento' => 'DNI', 'numero_documento' => '28999888']);
        $servicio = $this->crearServicio($cliente->id_cliente, $plan->id_plan);

        // Ticket resuelto en 4 horas
        Ticket::create([
            'id_servicio' => $servicio->id_servicio,
            'id_usuario_asignado' => $admin->id_usuario,
            'tipo' => 'tecnico',
            'descripcion' => 'Reinicio OLT',
            'estado' => EstadoTicket::Resuelto,
            'fecha_creacion' => '2026-10-05 10:00:00',
            'fecha_resolucion' => '2026-10-05 14:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('reportes.tickets', [
            'fecha_desde' => '2026-10-01',
            'fecha_hasta' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertSee('Abelardo Reclamos');
        $response->assertSee('4 hs');
        $response->assertSee('100%');
    }

    public function test_alertas_de_vencimiento_y_notificacion_whatsapp(): void
    {
        $admin = $this->crearAdmin();
        $plan = Plan::create(['nombre' => 'Plan Alerta', 'velocidad' => 30, 'precio_vigente' => 10000]);
        $cliente = Cliente::create(['nombre_razon_social' => 'Mariana Alerta', 'tipo_documento' => 'DNI', 'numero_documento' => '35123456', 'telefono_whatsapp' => '5493704998877']);
        $servicio = $this->crearServicio($cliente->id_cliente, $plan->id_plan);

        $cuota = Cuota::create([
            'id_servicio' => $servicio->id_servicio,
            'periodo' => '2026-11',
            'monto' => 10000,
            'fecha_emision' => '2026-11-01',
            'fecha_vencimiento' => now()->addDays(2)->toDateString(),
            'estado' => EstadoCuota::Pendiente,
        ]);

        $response = $this->actingAs($admin)->get(route('reportes.alertas'));
        $response->assertOk();
        $response->assertSee('Mariana Alerta');
        $response->assertSee('PRÓXIMO VENCIMIENTO');

        // Notificar por WhatsApp
        $responseNotif = $this->actingAs($admin)->post(route('reportes.alertas.notificar'), [
            'id_cuota' => $cuota->id_cuota,
        ]);
        $responseNotif->assertRedirect();
        $responseNotif->assertSessionHas('exito');
    }

    public function test_exportar_reporte_morosidad_a_csv(): void
    {
        $admin = $this->crearAdmin();

        $response = $this->actingAs($admin)->get(route('reportes.exportar', [
            'tipo' => 'morosidad',
            'formato' => 'csv',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Reporte_Morosidad_VillafaneWifi', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_exportar_reporte_a_imprimir_pdf(): void
    {
        $admin = $this->crearAdmin();

        $response = $this->actingAs($admin)->get(route('reportes.exportar', [
            'tipo' => 'ingresos',
            'formato' => 'imprimir',
        ]));

        $response->assertOk();
        $response->assertViewIs('reportes.imprimir');
        $response->assertSee('VILLAFAÑE WIFI');
        $response->assertSee('Documento Oficial Certificado');
        $response->assertSee('Imprimir / Guardar como PDF');
    }
}
