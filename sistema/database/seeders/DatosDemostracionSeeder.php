<?php

namespace Database\Seeders;

use App\Enums\EstadoCuota;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Servicio;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosDemostracionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $planes = $this->crearPlanes();
            $clientes = $this->crearClientes();
            $cuentas = $this->crearCuentasReceptoras();
            $servicios = $this->crearServicios($clientes, $planes);

            $this->crearCuotasYPagos($servicios, $cuentas);
            $this->crearConversaciones($clientes);
        });
    }

    /** @return array<string, Plan> */
    private function crearPlanes(): array
    {
        $datos = [
            'hogar' => ['nombre' => 'Hogar 20', 'velocidad' => '20 Mbps', 'precio_vigente' => '18000.00'],
            'plus' => ['nombre' => 'Hogar Plus 50', 'velocidad' => '50 Mbps', 'precio_vigente' => '26000.00'],
            'comercio' => ['nombre' => 'Comercio 100', 'velocidad' => '100 Mbps', 'precio_vigente' => '42000.00'],
        ];

        return collect($datos)->mapWithKeys(function (array $plan, string $clave): array {
            $modelo = Plan::updateOrCreate(['nombre' => $plan['nombre']], $plan + ['estado' => 'activo']);

            return [$clave => $modelo];
        })->all();
    }

    /** @return array<string, Cliente> */
    private function crearClientes(): array
    {
        $datos = [
            'ana' => ['tipo_documento' => 'DNI', 'numero_documento' => '30123456', 'nombre_razon_social' => 'Ana Gómez', 'tipo_cliente' => 'particular', 'calle_contacto' => 'Belgrano', 'numero_contacto' => '245', 'localidad_contacto' => 'Villafañe', 'telefono_whatsapp' => '5493718123401', 'estado' => 'activo'],
            'roberto' => ['tipo_documento' => 'DNI', 'numero_documento' => '27876543', 'nombre_razon_social' => 'Roberto Díaz', 'tipo_cliente' => 'particular', 'calle_contacto' => 'San Martín', 'numero_contacto' => '118', 'localidad_contacto' => 'El Colorado', 'telefono_whatsapp' => '5493704123402', 'estado' => 'activo'],
            'lapacho' => ['tipo_documento' => 'CUIT', 'numero_documento' => '30712345678', 'nombre_razon_social' => 'Kiosco El Lapacho', 'tipo_cliente' => 'comercio', 'calle_contacto' => 'Sarmiento', 'numero_contacto' => '602', 'localidad_contacto' => 'Villafañe', 'telefono_whatsapp' => '5493718123403', 'estado' => 'activo'],
            'norte' => ['tipo_documento' => 'CUIT', 'numero_documento' => '30765432109', 'nombre_razon_social' => 'Librería Norte', 'tipo_cliente' => 'comercio', 'calle_contacto' => 'Rivadavia', 'numero_contacto' => '411', 'localidad_contacto' => 'Pirané', 'telefono_whatsapp' => '5493704123404', 'estado' => 'suspendido'],
            'marta' => ['tipo_documento' => 'DNI', 'numero_documento' => '33456789', 'nombre_razon_social' => 'Marta Benítez', 'tipo_cliente' => 'particular', 'calle_contacto' => 'Moreno', 'numero_contacto' => '87', 'localidad_contacto' => 'Villa Dos Trece', 'telefono_whatsapp' => '5493718123405', 'estado' => 'activo'],
            'panaderia' => ['tipo_documento' => 'CUIT', 'numero_documento' => '30678901234', 'nombre_razon_social' => 'Panadería La Estación', 'tipo_cliente' => 'comercio', 'calle_contacto' => 'Mitre', 'numero_contacto' => '930', 'localidad_contacto' => 'Villafañe', 'telefono_whatsapp' => '5493718123406', 'estado' => 'activo'],
        ];

        return collect($datos)->mapWithKeys(function (array $cliente, string $clave): array {
            $modelo = Cliente::updateOrCreate([
                'tipo_documento' => $cliente['tipo_documento'],
                'numero_documento' => $cliente['numero_documento'],
            ], $cliente);

            return [$clave => $modelo];
        })->all();
    }

    /** @return array<string, CuentaReceptora> */
    private function crearCuentasReceptoras(): array
    {
        $datos = [
            'mercado_pago' => ['nombre' => 'Mercado Pago Villafañe Wifi', 'tipo' => 'mercado_pago', 'identificador' => 'villafanewifi.mp', 'estado' => 'activa'],
            'banco' => ['nombre' => 'Cuenta bancaria principal', 'tipo' => 'banco', 'identificador' => 'CBU-DEMO-0001', 'estado' => 'activa'],
        ];

        return collect($datos)->mapWithKeys(function (array $cuenta, string $clave): array {
            $modelo = CuentaReceptora::updateOrCreate([
                'tipo' => $cuenta['tipo'],
                'identificador' => $cuenta['identificador'],
            ], $cuenta);

            return [$clave => $modelo];
        })->all();
    }

    /**
     * @param  array<string, Cliente>  $clientes
     * @param  array<string, Plan>  $planes
     * @return array<string, Servicio>
     */
    private function crearServicios(array $clientes, array $planes): array
    {
        $datos = [
            'ana' => [$clientes['ana'], $planes['hogar'], 'Belgrano', '245', 'Villafañe', 10, '192.168.10.11', '02:00:00:00:00:11', 'activo'],
            'roberto' => [$clientes['roberto'], $planes['plus'], 'San Martín', '118', 'El Colorado', 15, '192.168.10.12', '02:00:00:00:00:12', 'activo'],
            'lapacho' => [$clientes['lapacho'], $planes['comercio'], 'Sarmiento', '602', 'Villafañe', 5, '192.168.10.13', '02:00:00:00:00:13', 'activo'],
            'norte' => [$clientes['norte'], $planes['plus'], 'Rivadavia', '411', 'Pirané', 12, '192.168.10.14', '02:00:00:00:00:14', 'suspendido'],
            'marta' => [$clientes['marta'], $planes['hogar'], 'Moreno', '87', 'Villa Dos Trece', 20, '192.168.10.15', '02:00:00:00:00:15', 'activo'],
            'panaderia' => [$clientes['panaderia'], $planes['comercio'], 'Mitre', '930', 'Villafañe', 8, '192.168.10.16', '02:00:00:00:00:16', 'activo'],
        ];

        return collect($datos)->mapWithKeys(function (array $servicio, string $clave): array {
            [$cliente, $plan, $calle, $numero, $localidad, $dia, $ipv4, $mac, $estado] = $servicio;
            $modelo = Servicio::updateOrCreate(
                ['id_cliente' => $cliente->id_cliente, 'ipv4' => $ipv4],
                [
                    'id_plan' => $plan->id_plan,
                    'calle_instalacion' => $calle,
                    'numero_instalacion' => $numero,
                    'localidad_instalacion' => $localidad,
                    'dia_vencimiento' => $dia,
                    'proximo_vencimiento' => null,
                    'fecha_alta' => CarbonImmutable::today()->subMonthsNoOverflow(8)->toDateString(),
                    'mac' => $mac,
                    'estado' => $estado,
                ],
            );
            $modelo->setRelation('plan', $plan);

            return [$clave => $modelo];
        })->all();
    }

    /**
     * @param  array<string, Servicio>  $servicios
     * @param  array<string, CuentaReceptora>  $cuentas
     */
    private function crearCuotasYPagos(array $servicios, array $cuentas): void
    {
        $hoy = CarbonImmutable::today();
        $meses = collect([2, 1, 0])->map(
            fn (int $mesesAtras): CarbonImmutable => $hoy->subMonthsNoOverflow($mesesAtras)->startOfMonth(),
        );

        foreach ($servicios as $servicio) {
            foreach ($meses as $mes) {
                $vencimiento = $mes->day(min($servicio->dia_vencimiento, $mes->daysInMonth));
                Cuota::updateOrCreate(
                    ['id_servicio' => $servicio->id_servicio, 'periodo' => $mes->format('Y-m')],
                    [
                        'monto' => $servicio->plan->precio_vigente,
                        'fecha_emision' => $mes->toDateString(),
                        'fecha_vencimiento' => $vencimiento->toDateString(),
                        'estado' => $vencimiento->isBefore($hoy) ? EstadoCuota::Vencida->value : EstadoCuota::Pendiente->value,
                    ],
                );
            }
        }

        $this->registrarPagoDemostracion($servicios['ana'], $cuentas['mercado_pago'], $meses->first());
        $this->registrarPagoDemostracion($servicios['lapacho'], $cuentas['banco'], $meses->first());

        foreach ($servicios as $servicio) {
            $proximoVencimiento = $servicio->cuotas()
                ->whereNull('id_pago')
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->orderBy('fecha_vencimiento')
                ->value('fecha_vencimiento');
            $servicio->update(['proximo_vencimiento' => $proximoVencimiento]);
        }
    }

    private function registrarPagoDemostracion(Servicio $servicio, CuentaReceptora $cuenta, CarbonImmutable $mes): void
    {
        $cuota = Cuota::where('id_servicio', $servicio->id_servicio)
            ->where('periodo', $mes->format('Y-m'))
            ->sole();
        $datosPago = [
            'id_cuenta' => $cuenta->id_cuenta,
            'fecha' => $cuota->fecha_vencimiento->toDateString(),
            'monto_total' => $cuota->monto,
            'medio_pago' => 'transferencia',
            'id_comprobante' => null,
        ];
        $pago = $cuota->id_pago === null
            ? Pago::create($datosPago)
            : tap(Pago::findOrFail($cuota->id_pago))->update($datosPago);
        $cuota->update(['id_pago' => $pago->id_pago, 'estado' => EstadoCuota::Pagada->value]);
    }

    /**
     * Crea historiales ficticios para revisar la pantalla de conversaciones.
     * Los identificadores externos evitan duplicar mensajes al repetir el seed.
     *
     * @param  array<string, Cliente>  $clientes
     */
    private function crearConversaciones(array $clientes): void
    {
        $ejemplos = [
            [
                'cliente' => $clientes['ana'],
                'inicio' => '2026-09-10 09:15:00',
                'estado' => 'abierta',
                'mensajes' => [
                    ['wamid.demo.ana.1', '2026-09-10 09:15:00', 'cliente', 'Hola, quiero consultar mi cuenta', 'recibido'],
                    ['wamid.demo.ana.2', '2026-09-10 09:15:03', 'bot', "Hola, Ana Gómez. Soy el asistente virtual de Villafañe Wifi.\n\nElegí una opción respondiendo con su número:\n1. Consultar estado de cuenta\n2. Informar un pago\n3. Registrar un reclamo\n4. Solicitar atención humana", 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['lapacho'],
                'inicio' => '2026-09-09 16:40:00',
                'estado' => 'cerrada',
                'mensajes' => [
                    ['wamid.demo.lapacho.1', '2026-09-09 16:40:00', 'cliente', 'Buenas tardes, necesito informar un pago', 'recibido'],
                    ['wamid.demo.lapacho.2', '2026-09-09 16:40:02', 'bot', 'Recibimos tu mensaje. Te ayudaremos a registrar el pago.', 'leido'],
                ],
            ],
        ];

        foreach ($ejemplos as $ejemplo) {
            $conversacion = Conversacion::firstOrCreate(
                [
                    'id_cliente' => $ejemplo['cliente']->id_cliente,
                    'fecha_hora_inicio' => $ejemplo['inicio'],
                ],
                [
                    'numero_whatsapp' => $ejemplo['cliente']->telefono_whatsapp,
                    'fecha_hora_cierre' => $ejemplo['estado'] === 'cerrada' ? '2026-09-09 16:45:00' : null,
                    'estado' => $ejemplo['estado'],
                    'modo_atencion' => 'bot',
                ],
            );

            foreach ($ejemplo['mensajes'] as [$identificador, $fecha, $emisor, $contenido, $estado]) {
                Mensaje::updateOrCreate(
                    ['id_mensaje_externo' => $identificador],
                    [
                        'id_conversacion' => $conversacion->id_conversacion,
                        'id_usuario' => null,
                        'fecha_hora' => $fecha,
                        'tipo' => 'texto',
                        'contenido' => $contenido,
                        'archivo_adjunto' => null,
                        'tipo_emisor' => $emisor,
                        'estado_envio' => $estado,
                    ],
                );
            }
        }
    }
}
