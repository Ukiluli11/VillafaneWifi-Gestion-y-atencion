<?php

namespace Database\Seeders;

use App\Dominio\ServicioConsultaCuentaWhatsapp;
use App\Enums\EstadoCuota;
use App\Models\AvisoVencimiento;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Conversacion;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use App\Models\Mensaje;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Ticket;
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
            $this->crearReclamos($clientes, $servicios);
            $this->crearAvisosVencimiento($clientes, $servicios);
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
            'lucia' => ['tipo_documento' => 'DNI', 'numero_documento' => '35111222', 'nombre_razon_social' => 'Lucía Fernández', 'tipo_cliente' => 'particular', 'calle_contacto' => 'España', 'numero_contacto' => '315', 'localidad_contacto' => 'Villafañe', 'telefono_whatsapp' => '5493704000098', 'estado' => 'activo'],
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
            'lucia' => [$clientes['lucia'], $planes['hogar'], 'España', '315', 'Villafañe', 12, '192.168.10.17', '02:00:00:00:00:17', 'activo'],
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
        $presentacionBot = "Hola. Te atiende el servicio virtual de Villafañe Wifi.\n\n";
        $respuestaCuentaAna = $presentacionBot
            .app(ServicioConsultaCuentaWhatsapp::class)->crearRespuesta($clientes['ana']);
        $respuestaCuentaRoberto = $presentacionBot
            .app(ServicioConsultaCuentaWhatsapp::class)->crearRespuesta($clientes['roberto']);
        $menu = "Elegí una opción respondiendo con su número:\n"
            ."1. Consultar estado de cuenta\n2. Informar un pago\n"
            ."3. Registrar un reclamo\n4. Solicitar atención humana";
        $ejemplos = [
            [
                'cliente' => $clientes['ana'],
                'inicio' => '2026-09-10 09:15:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-10 09:16:00',
                'mensajes' => [
                    ['wamid.demo.ana.1', '2026-09-10 09:15:00', 'cliente', 'Hola, quiero consultar mi cuenta', 'recibido'],
                    ['wamid.demo.ana.2', '2026-09-10 09:15:03', 'bot', "Hola, Ana Gómez. Te atiende el servicio virtual de Villafañe Wifi.\n\n{$menu}", 'entregado'],
                    ['wamid.demo.ana.3', '2026-09-10 09:15:20', 'cliente', '1', 'recibido'],
                    ['wamid.demo.ana.4', '2026-09-10 09:15:22', 'bot', $respuestaCuentaAna, 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['lapacho'],
                'inicio' => '2026-09-09 16:40:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-09 16:45:00',
                'mensajes' => [
                    ['wamid.demo.lapacho.1', '2026-09-09 16:40:00', 'cliente', 'Buenas tardes, necesito informar un pago', 'recibido'],
                    ['wamid.demo.lapacho.2', '2026-09-09 16:40:02', 'bot', $presentacionBot.'Para informar el pago, enviá ahora una foto o un archivo PDF del comprobante.', 'leido'],
                ],
            ],
            [
                'cliente' => $clientes['roberto'],
                'inicio' => '2026-09-12 11:20:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-12 11:22:00',
                'mensajes' => [
                    ['wamid.demo.roberto.1', '2026-09-12 11:20:00', 'cliente', 'Buen día', 'recibido'],
                    ['wamid.demo.roberto.2', '2026-09-12 11:20:02', 'bot', "Hola, Roberto Díaz. Te atiende el servicio virtual de Villafañe Wifi.\n\n{$menu}", 'leido'],
                    ['wamid.demo.roberto.3', '2026-09-12 11:21:00', 'cliente', 'Quiero ver mi deuda', 'recibido'],
                    ['wamid.demo.roberto.4', '2026-09-12 11:21:02', 'bot', $respuestaCuentaRoberto, 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['norte'],
                'inicio' => '2026-09-13 08:35:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-13 08:38:00',
                'mensajes' => [
                    ['wamid.demo.norte.1', '2026-09-13 08:35:00', 'cliente', 'Hola, necesito conocer las opciones', 'recibido'],
                    ['wamid.demo.norte.2', '2026-09-13 08:35:02', 'bot', $presentacionBot.$menu, 'leido'],
                ],
            ],
            [
                'cliente' => $clientes['marta'],
                'inicio' => '2026-09-14 18:05:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-14 18:06:05',
                'mensajes' => [
                    ['wamid.demo.marta.1', '2026-09-14 18:05:00', 'cliente', 'Hola, acabo de pagar', 'recibido'],
                    ['wamid.demo.marta.2', '2026-09-14 18:05:02', 'bot', "Hola, Marta Benítez. Te atiende el servicio virtual de Villafañe Wifi.\n\n{$menu}", 'leido'],
                    ['wamid.demo.marta.3', '2026-09-14 18:05:20', 'cliente', '2', 'recibido'],
                    ['wamid.demo.marta.4', '2026-09-14 18:05:22', 'bot', $presentacionBot.'Para informar el pago, enviá ahora una foto o un archivo PDF del comprobante.', 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['panaderia'],
                'inicio' => '2026-09-15 07:50:00',
                'estado' => 'escalada',
                'modo_atencion' => 'usuario_interno',
                'mensajes' => [
                    ['wamid.demo.panaderia.1', '2026-09-15 07:50:00', 'cliente', 'Hola', 'recibido'],
                    ['wamid.demo.panaderia.2', '2026-09-15 07:50:02', 'bot', "Hola, Panadería La Estación. Te atiende el servicio virtual de Villafañe Wifi.\n\n{$menu}", 'leido'],
                    ['wamid.demo.panaderia.3', '2026-09-15 07:51:00', 'cliente', 'menú', 'recibido'],
                    ['wamid.demo.panaderia.4', '2026-09-15 07:51:02', 'bot', $presentacionBot."Menú principal:\n".$menu, 'entregado'],
                    ['wamid.demo.panaderia.5', '2026-09-15 07:52:00', 'cliente', '4', 'recibido'],
                    ['wamid.demo.panaderia.6', '2026-09-15 07:52:02', 'bot', $presentacionBot.'Derivamos tu consulta al primer empleado disponible. La atención continuará por este mismo chat.', 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['lucia'],
                'inicio' => '2026-09-16 09:00:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-16 09:05:30',
                'mensajes' => [
                    ['wamid.demo.lucia.registro.1', '2026-09-16 09:00:00', 'cliente', 'Hola, quiero contratar Internet', 'recibido'],
                    ['wamid.demo.lucia.registro.2', '2026-09-16 09:00:02', 'bot', $presentacionBot.'No encontramos un cliente asociado a este número. Escribí tu DNI para identificarte o comenzar el registro.', 'entregado'],
                    ['wamid.demo.lucia.registro.3', '2026-09-16 09:00:30', 'cliente', '35111222', 'recibido'],
                    ['wamid.demo.lucia.registro.4', '2026-09-16 09:00:32', 'bot', $presentacionBot.'Ese DNI no está registrado. Para darte de alta, escribí tu nombre y apellido completos.', 'entregado'],
                    ['wamid.demo.lucia.registro.5', '2026-09-16 09:01:00', 'cliente', 'Lucía Fernández', 'recibido'],
                    ['wamid.demo.lucia.registro.6', '2026-09-16 09:01:02', 'bot', $presentacionBot.'Indicá tu domicilio de contacto con el formato calle | número | localidad.', 'entregado'],
                    ['wamid.demo.lucia.registro.7', '2026-09-16 09:01:30', 'cliente', 'España | 315 | Villafañe', 'recibido'],
                    ['wamid.demo.lucia.registro.8', '2026-09-16 09:01:32', 'bot', $presentacionBot."Elegí el plan para tu nuevo servicio:\n1. Hogar 20 - 20 Mbps - $18.000\n2. Hogar Plus 50 - 50 Mbps - $26.000\n3. Comercio 100 - 100 Mbps - $42.000", 'entregado'],
                    ['wamid.demo.lucia.registro.9', '2026-09-16 09:02:00', 'cliente', '1', 'recibido'],
                    ['wamid.demo.lucia.registro.10', '2026-09-16 09:02:02', 'bot', $presentacionBot.'Indicá el domicilio de instalación con el formato calle | número | localidad.', 'entregado'],
                    ['wamid.demo.lucia.registro.11', '2026-09-16 09:02:30', 'cliente', 'España | 315 | Villafañe', 'recibido'],
                    ['wamid.demo.lucia.registro.12', '2026-09-16 09:02:32', 'bot', $presentacionBot.'Elegí el día de vencimiento mensual, entre 1 y 28.', 'entregado'],
                    ['wamid.demo.lucia.registro.13', '2026-09-16 09:03:00', 'cliente', '12', 'recibido'],
                    ['wamid.demo.lucia.registro.14', '2026-09-16 09:03:02', 'bot', $presentacionBot.'Registro completado. Se creó el cliente Lucía Fernández con el plan Hogar 20 y vencimiento el día 12.', 'leido'],
                ],
            ],
            [
                'cliente' => $clientes['roberto'],
                'inicio' => '2026-09-16 10:10:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-16 10:13:00',
                'mensajes' => [
                    ['wamid.demo.roberto.ticket.1', '2026-09-16 10:10:00', 'cliente', 'Hola, quiero registrar un reclamo', 'recibido'],
                    ['wamid.demo.roberto.ticket.2', '2026-09-16 10:10:02', 'bot', "Hola, Roberto Díaz. Te atiende el servicio virtual de Villafañe Wifi.\n\nDescribí brevemente el problema con tu servicio.", 'entregado'],
                ],
            ],
            [
                'cliente' => $clientes['norte'],
                'inicio' => '2026-09-16 15:30:00',
                'estado' => 'cerrada',
                'cierre' => '2026-09-16 15:35:00',
                'mensajes' => [
                    ['wamid.demo.estados.1', '2026-09-16 15:30:00', 'cliente', 'Necesito revisar el seguimiento de mensajes', 'recibido'],
                    ['wamid.demo.estados.2', '2026-09-16 15:30:05', 'bot', $presentacionBot.'Mensaje preparado para envío.', 'pendiente'],
                    ['wamid.demo.estados.3', '2026-09-16 15:31:00', 'bot', $presentacionBot.'Mensaje aceptado por Meta.', 'enviado'],
                    ['wamid.demo.estados.4', '2026-09-16 15:32:00', 'bot', $presentacionBot.'Mensaje entregado al dispositivo.', 'entregado'],
                    ['wamid.demo.estados.5', '2026-09-16 15:33:00', 'bot', $presentacionBot.'Mensaje leído por el destinatario.', 'leido'],
                    ['wamid.demo.estados.6', '2026-09-16 15:34:00', 'bot', $presentacionBot.'Ejemplo controlado de un envío fallido.', 'fallido'],
                ],
            ],
        ];

        foreach ($ejemplos as $ejemplo) {
            $conversacion = Conversacion::updateOrCreate(
                [
                    'id_cliente' => $ejemplo['cliente']->id_cliente,
                    'fecha_hora_inicio' => $ejemplo['inicio'],
                ],
                [
                    'numero_whatsapp' => $ejemplo['cliente']->telefono_whatsapp,
                    'fecha_hora_cierre' => $ejemplo['cierre'] ?? null,
                    'estado' => $ejemplo['estado'],
                    'modo_atencion' => $ejemplo['modo_atencion'] ?? 'bot',
                    'estado_flujo' => 'menu',
                    'intentos_intencion' => 0,
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

            if ($ejemplo['cliente']->is($clientes['lapacho'])) {
                $mensajeComprobante = Mensaje::updateOrCreate(
                    ['id_mensaje_externo' => 'wamid.demo.lapacho.3'],
                    [
                        'id_conversacion' => $conversacion->id_conversacion,
                        'id_usuario' => null,
                        'fecha_hora' => '2026-09-09 16:41:00',
                        'tipo' => 'imagen',
                        'contenido' => 'Transferencia de septiembre',
                        'archivo_adjunto' => 'meta-media:demo-comprobante-lapacho',
                        'tipo_emisor' => 'cliente',
                        'estado_envio' => 'recibido',
                    ],
                );
                Comprobante::updateOrCreate(
                    ['id_mensaje' => $mensajeComprobante->id_mensaje],
                    [
                        'fecha_recepcion' => $mensajeComprobante->fecha_hora,
                        'estado_validacion' => 'pendiente',
                    ],
                );
            }

            if ($ejemplo['cliente']->is($clientes['marta'])) {
                $mensajeComprobante = Mensaje::updateOrCreate(
                    ['id_mensaje_externo' => 'wamid.demo.marta.5'],
                    [
                        'id_conversacion' => $conversacion->id_conversacion,
                        'id_usuario' => null,
                        'fecha_hora' => '2026-09-14 18:06:00',
                        'tipo' => 'documento',
                        'contenido' => 'Comprobante de pago en PDF',
                        'archivo_adjunto' => 'meta-media:demo-comprobante-marta',
                        'tipo_emisor' => 'cliente',
                        'estado_envio' => 'recibido',
                    ],
                );
                Comprobante::updateOrCreate(
                    ['id_mensaje' => $mensajeComprobante->id_mensaje],
                    [
                        'fecha_recepcion' => $mensajeComprobante->fecha_hora,
                        'estado_validacion' => 'pendiente',
                    ],
                );
            }
        }

        $registro = Conversacion::updateOrCreate(
            ['numero_whatsapp' => '5493704000099', 'fecha_hora_inicio' => '2026-09-17 12:00:00'],
            [
                'id_cliente' => null,
                'estado' => 'abierta',
                'modo_atencion' => 'bot',
                'estado_flujo' => 'esperando_nombre_registro',
                'datos_registro' => ['tipo_documento' => 'DNI', 'numero_documento' => '35111222'],
                'intentos_intencion' => 0,
            ],
        );
        $mensajesRegistro = [
            ['wamid.demo.registro.1', '2026-09-17 12:00:00', 'cliente', 'Hola, quiero contratar Internet', 'recibido'],
            ['wamid.demo.registro.2', '2026-09-17 12:00:02', 'bot', $presentacionBot.'No encontramos un cliente asociado a este número. Escribí tu DNI para identificarte o comenzar el registro.', 'entregado'],
            ['wamid.demo.registro.3', '2026-09-17 12:00:20', 'cliente', '35111222', 'recibido'],
            ['wamid.demo.registro.4', '2026-09-17 12:00:22', 'bot', $presentacionBot.'Ese DNI no está registrado. Para darte de alta, escribí tu nombre y apellido completos.', 'entregado'],
        ];
        foreach ($mensajesRegistro as [$identificador, $fecha, $emisor, $contenido, $estado]) {
            Mensaje::updateOrCreate(
                ['id_mensaje_externo' => $identificador],
                [
                    'id_conversacion' => $registro->id_conversacion,
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

    /**
     * Agrega reclamos técnicos y administrativos en distintos estados.
     *
     * @param  array<string, Cliente>  $clientes
     * @param  array<string, Servicio>  $servicios
     */
    private function crearReclamos(array $clientes, array $servicios): void
    {
        $escenarios = [
            [
                'cliente' => $clientes['roberto'],
                'servicio' => $servicios['roberto'],
                'mensaje' => 'wamid.demo.roberto.reclamo',
                'fecha' => '2026-09-16 10:11:00',
                'inicio_conversacion' => '2026-09-16 10:10:00',
                'tipo' => 'tecnico',
                'descripcion' => 'La conexión se corta varias veces durante la tarde.',
                'estado' => 'abierto',
            ],
            [
                'cliente' => $clientes['norte'],
                'servicio' => $servicios['norte'],
                'mensaje' => 'wamid.demo.norte.reclamo',
                'fecha' => '2026-09-13 08:36:00',
                'tipo' => 'administrativo',
                'descripcion' => 'Solicita revisar el estado suspendido del servicio.',
                'estado' => 'resuelto',
                'fecha_resolucion' => '2026-09-13 10:20:00',
            ],
        ];

        foreach ($escenarios as $escenario) {
            $consultaConversacion = Conversacion::query()
                ->where('id_cliente', $escenario['cliente']->id_cliente);
            $conversacion = isset($escenario['inicio_conversacion'])
                ? $consultaConversacion->where('fecha_hora_inicio', $escenario['inicio_conversacion'])->firstOrFail()
                : $consultaConversacion->oldest('fecha_hora_inicio')->firstOrFail();
            $mensaje = Mensaje::updateOrCreate(
                ['id_mensaje_externo' => $escenario['mensaje']],
                [
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_usuario' => null,
                    'fecha_hora' => $escenario['fecha'],
                    'tipo' => 'texto',
                    'contenido' => $escenario['descripcion'],
                    'archivo_adjunto' => null,
                    'tipo_emisor' => 'cliente',
                    'estado_envio' => 'recibido',
                ],
            );
            $ticket = Ticket::updateOrCreate(
                [
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_servicio' => $escenario['servicio']->id_servicio,
                    'descripcion' => $escenario['descripcion'],
                ],
                [
                    'id_empleado' => null,
                    'fecha_creacion' => $mensaje->fecha_hora,
                    'tipo' => $escenario['tipo'],
                    'estado' => $escenario['estado'],
                    'fecha_resolucion' => $escenario['fecha_resolucion'] ?? null,
                    'fecha_asignacion' => null,
                ],
            );

            Mensaje::updateOrCreate(
                ['id_mensaje_externo' => $escenario['mensaje'].'.confirmacion'],
                [
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_usuario' => null,
                    'fecha_hora' => CarbonImmutable::parse($escenario['fecha'])->addSeconds(2),
                    'tipo' => 'texto',
                    'contenido' => "Hola. Te atiende el servicio virtual de Villafañe Wifi. Registramos el reclamo con el ticket #{$ticket->id_ticket}. Será atendido por orden de llegada.",
                    'archivo_adjunto' => null,
                    'tipo_emisor' => 'bot',
                    'estado_envio' => 'entregado',
                ],
            );
        }
    }

    /**
     * Crea un aviso próximo y otro vencido sin contactar servicios externos.
     *
     * @param  array<string, Cliente>  $clientes
     * @param  array<string, Servicio>  $servicios
     */
    private function crearAvisosVencimiento(array $clientes, array $servicios): void
    {
        $periodo = CarbonImmutable::today()->format('Y-m');
        $escenarios = [
            [
                'cliente' => $clientes['ana'],
                'cuota' => Cuota::where('id_servicio', $servicios['ana']->id_servicio)->where('periodo', $periodo)->firstOrFail(),
                'tipo' => 'vencido',
                'identificador' => 'wamid.demo.aviso.ana',
                'fecha' => '2026-09-17 09:00:00',
                'contenido' => 'Hola. Te atiende el servicio virtual de Villafañe Wifi. La cuota del período actual se encuentra vencida y continúa pendiente.',
            ],
            [
                'cliente' => $clientes['marta'],
                'cuota' => Cuota::where('id_servicio', $servicios['marta']->id_servicio)->where('periodo', $periodo)->firstOrFail(),
                'tipo' => 'proximo',
                'identificador' => 'wamid.demo.aviso.marta',
                'fecha' => '2026-09-17 09:05:00',
                'contenido' => 'Hola. Te atiende el servicio virtual de Villafañe Wifi. Te recordamos que la cuota del período actual vence próximamente.',
            ],
        ];

        foreach ($escenarios as $escenario) {
            $mensaje = Mensaje::where('id_mensaje_externo', $escenario['identificador'])->first();
            $conversacion = $mensaje?->conversacion;
            if ($conversacion === null) {
                $conversacion = Conversacion::create([
                    'id_cliente' => $escenario['cliente']->id_cliente,
                    'numero_whatsapp' => $escenario['cliente']->telefono_whatsapp,
                    'fecha_hora_inicio' => $escenario['fecha'],
                    'fecha_hora_cierre' => $escenario['fecha'],
                    'estado' => 'cerrada',
                    'modo_atencion' => 'bot',
                    'estado_flujo' => 'menu',
                    'intentos_intencion' => 0,
                ]);
                $mensaje = Mensaje::create([
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_usuario' => null,
                    'id_mensaje_externo' => $escenario['identificador'],
                    'fecha_hora' => $escenario['fecha'],
                    'tipo' => 'texto',
                    'contenido' => $escenario['contenido'],
                    'archivo_adjunto' => null,
                    'tipo_emisor' => 'bot',
                    'estado_envio' => 'entregado',
                ]);
            }

            AvisoVencimiento::updateOrCreate(
                ['id_cuota' => $escenario['cuota']->id_cuota, 'tipo' => $escenario['tipo']],
                [
                    'id_conversacion' => $conversacion->id_conversacion,
                    'id_mensaje' => $mensaje->id_mensaje,
                    'estado' => 'enviado',
                    'fecha_hora_ultimo_intento' => $escenario['fecha'],
                    'fecha_hora_envio' => $escenario['fecha'],
                    'detalle_error' => null,
                ],
            );
        }
    }
}
