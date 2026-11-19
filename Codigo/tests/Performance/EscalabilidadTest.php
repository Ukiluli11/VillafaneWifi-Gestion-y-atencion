<?php

namespace Tests\Performance;

use App\Dominio\ServicioUsuarios;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Mide el comportamiento de operaciones del sistema al aumentar el volumen de datos.
 */
class EscalabilidadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_flujos_del_panel_con_historial_anual(): void
    {
        $conexion = config('database.default');
        $base = config("database.connections.{$conexion}.database");
        $this->assertTrue(
            ($conexion === 'sqlite' && $base === ':memory:')
            || ($conexion === 'mariadb'
                && config('database.connections.mariadb.host') === '127.0.0.1'
                && str_starts_with((string) $base, 'villafane_qa_')),
            'La prueba necesita una base de datos temporal.',
        );
        Http::preventStrayRequests();

        $fecha = CarbonImmutable::parse('2026-11-16 10:00', 'America/Argentina/Buenos_Aires');
        $this->travelTo($fecha);
        $admin = app(ServicioUsuarios::class)->crear(['nombre_usuario' => 'qa-escalabilidad', 'contrasena' => 'local-solamente', 'tipo' => 'administrador']);
        $plan = Plan::factory()->create(['precio_vigente' => '100.00']);
        $cuenta = CuentaReceptora::factory()->create();
        $filas = ['cuota' => [], 'pago' => [], 'mensaje' => []];

        for ($i = 1; $i <= 800; $i++) {
            $cliente = Cliente::factory()->create(['nombre_razon_social' => 'Cliente Q'.$i]);
            $servicio = Servicio::factory()->for($cliente)->for($plan)->create();
            $chat = Conversacion::factory()->for($cliente)->create(['numero_whatsapp' => $cliente->telefono_whatsapp]);
            if ($i === 1) {
                $clienteDePrueba = $cliente;
            }
            if ($i % 4 === 0) {
                Ticket::factory()->for($servicio)->for($chat)->create();
            }

            for ($mes = 0; $mes < 12; $mes++) {
                $emision = $fecha->startOfMonth()->subMonths($mes);
                $vencimiento = $emision->day(10 + $i % 12);
                $pagoId = null;
                if ($mes > 1 || ($mes === 1 && $i % 5 !== 0)) {
                    $pagoId = ($i - 1) * 12 + $mes;
                    $filas['pago'][] = ['id_pago' => $pagoId, 'id_cuenta' => $cuenta->id_cuenta,
                        'fecha' => $vencimiento->toDateString(), 'monto_total' => '100.00', 'medio_pago' => 'transferencia'];
                }
                $filas['cuota'][] = ['id_servicio' => $servicio->id_servicio, 'id_pago' => $pagoId, 'periodo' => $emision->format('Y-m'),
                    'monto' => '100.00', 'fecha_emision' => $emision->toDateString(), 'fecha_vencimiento' => $vencimiento->toDateString(),
                    'estado' => $pagoId ? 'pagada' : ($vencimiento < $fecha ? 'vencida' : 'pendiente')];
            }

            for ($mensaje = 1; $mensaje <= 5; $mensaje++) {
                $filas['mensaje'][] = ['id_conversacion' => $chat->id_conversacion, 'id_mensaje_externo' => "qa.$i.$mensaje",
                    'fecha_hora' => $fecha->subDays($mensaje)->toDateTimeString(), 'tipo' => 'texto',
                    'contenido' => 'Consulta de prueba', 'tipo_emisor' => 'cliente', 'estado_envio' => 'recibido'];
            }

            if ($i === 100 || $i === 800) {
                foreach (['pago', 'cuota', 'mensaje'] as $tabla) {
                    foreach (array_chunk($filas[$tabla], 100) as $lote) {
                        DB::table($tabla)->insert($lote);
                    }
                    $filas[$tabla] = [];
                }
                $rutas = [
                    'clientes' => route('clientes.index', ['buscar' => 'Cliente Q', 'estado' => 'activo']),
                    'servicios' => route('servicios.index'),
                    'conversaciones' => route('conversaciones.index'),
                    'cuenta_corriente' => route('cuentas.show', $clienteDePrueba),
                    'soporte' => route('tickets.index'),
                    'dashboard' => route('reportes.dashboard'),
                    'morosidad' => route('reportes.morosidad'),
                    'alertas' => route('reportes.alertas'),
                ];
                $this->actingAs($admin);
                $resultados = [];
                foreach ($rutas as $nombre => $ruta) {
                    $this->get($ruta)->assertOk();
                    $tiempos = [];
                    for ($intento = 0; $intento < 5; $intento++) {
                        DB::flushQueryLog();
                        DB::enableQueryLog();
                        $inicio = hrtime(true);
                        $respuesta = $this->get($ruta);
                        $tiempos[] = (hrtime(true) - $inicio) / 1e6;
                        $consultas = count(DB::getQueryLog());
                        DB::disableQueryLog();
                        $respuesta->assertOk();
                    }
                    sort($tiempos);
                    $resultados[$nombre] = ['mediana_ms' => round($tiempos[2], 1), 'max_ms' => round($tiempos[4], 1),
                        'consultas' => $consultas, 'html_bytes' => strlen($respuesta->getContent())];
                }
                fwrite(STDOUT, "QA {$conexion} {$i} clientes ".json_encode($resultados, JSON_THROW_ON_ERROR).PHP_EOL);
            }
        }
    }
}
