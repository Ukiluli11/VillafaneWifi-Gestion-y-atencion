<?php

namespace App\Dominio;

use App\Enums\EstadoPlan;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Plan;

class ServicioRegistroConversacional
{
    public function __construct(
        private readonly ServicioClientes $servicioClientes,
        private readonly ServicioContrataciones $servicioContrataciones,
    ) {}

    public function procesar(Conversacion $conversacion, Mensaje $mensaje, ?Cliente $cliente): ?string
    {
        $contenido = trim((string) $mensaje->contenido);
        $paso = (string) ($conversacion->paso_actual ?? '');

        if ($cliente === null && mb_strtoupper($contenido) === 'REGISTRARME') {
            $this->actualizar($conversacion, 'registro_tipo_documento', []);

            return 'Iniciemos tu registro. Indicá el tipo de documento: DNI, CUIT o CUIL.';
        }

        if (! str_starts_with($paso, 'registro_')) {
            return null;
        }

        if (in_array(mb_strtoupper($contenido), ['OPERADOR', 'ASESOR', 'HUMANO'], true)) {
            $conversacion->update(['paso_actual' => null, 'contexto' => null]);
            $conversacion->escalar();

            return 'Te transferimos con un operador para continuar la gestión.';
        }

        return match ($paso) {
            'registro_tipo_documento' => $this->guardarTipoDocumento($conversacion, $contenido),
            'registro_numero_documento' => $this->guardarDocumento($conversacion, $contenido),
            'registro_nombre' => $this->guardarNombre($conversacion, $contenido),
            'registro_tipo_cliente' => $this->guardarTipoCliente($conversacion, $contenido),
            'registro_direccion_contacto' => $this->guardarDireccionContacto($conversacion, $contenido),
            'registro_plan' => $this->guardarPlan($conversacion, $contenido),
            'registro_direccion_instalacion' => $this->guardarDireccionInstalacion($conversacion, $contenido),
            'registro_dia_vencimiento' => $this->crearServicio($conversacion, $cliente, $contenido),
            default => null,
        };
    }

    private function guardarTipoDocumento(Conversacion $conversacion, string $contenido): string
    {
        $tipo = mb_strtoupper($contenido);
        if (! in_array($tipo, ['DNI', 'CUIT', 'CUIL'], true)) {
            return 'El tipo de documento debe ser DNI, CUIT o CUIL.';
        }

        $this->actualizar($conversacion, 'registro_numero_documento', ['tipo_documento' => $tipo]);

        return 'Ahora indicá el número de documento, solamente con dígitos.';
    }

    private function guardarDocumento(Conversacion $conversacion, string $contenido): string
    {
        $numero = preg_replace('/\D+/', '', $contenido) ?? '';
        if (strlen($numero) < 7 || strlen($numero) > 11) {
            return 'El número debe tener entre 7 y 11 dígitos. Volvé a ingresarlo.';
        }

        $contexto = $this->datos($conversacion);
        $existente = Cliente::query()
            ->where('tipo_documento', $contexto['tipo_documento'])
            ->where('numero_documento', $numero)
            ->first();

        if ($existente !== null) {
            $conversacion->update([
                'id_cliente' => $existente->id_cliente,
                'paso_actual' => null,
                'contexto' => null,
            ]);

            return "Encontré tu registro, {$existente->nombre_razon_social}. ¿Querés consultar tu cuenta, registrar un reclamo o hablar con un operador?";
        }

        $this->actualizar($conversacion, 'registro_nombre', ['numero_documento' => $numero]);

        return 'Indicá tu nombre completo o razón social.';
    }

    private function guardarNombre(Conversacion $conversacion, string $contenido): string
    {
        if (mb_strlen($contenido) < 3 || mb_strlen($contenido) > 150) {
            return 'El nombre o razón social debe tener entre 3 y 150 caracteres.';
        }

        $this->actualizar($conversacion, 'registro_tipo_cliente', ['nombre_razon_social' => $contenido]);

        return '¿El registro corresponde a un PARTICULAR o a un COMERCIO?';
    }

    private function guardarTipoCliente(Conversacion $conversacion, string $contenido): string
    {
        $tipo = mb_strtolower($contenido);
        if (! in_array($tipo, ['particular', 'comercio'], true)) {
            return 'Respondé PARTICULAR o COMERCIO para continuar.';
        }

        $this->actualizar($conversacion, 'registro_direccion_contacto', ['tipo_cliente' => $tipo]);

        return 'Indicá tu dirección de contacto con este formato: calle | número | localidad.';
    }

    private function guardarDireccionContacto(Conversacion $conversacion, string $contenido): string
    {
        $direccion = $this->extraerDireccion($contenido);
        if ($direccion === null) {
            return 'Usá el formato calle | número | localidad. Ejemplo: San Martín | 123 | Villafañe.';
        }

        $datos = array_merge($this->datos($conversacion), [
            'calle_contacto' => $direccion['calle'],
            'numero_contacto' => $direccion['numero'],
            'localidad_contacto' => $direccion['localidad'],
            'telefono_whatsapp' => $conversacion->numero_whatsapp,
        ]);
        $cliente = $this->servicioClientes->crear($datos);
        $conversacion->update([
            'id_cliente' => $cliente->id_cliente,
            'paso_actual' => 'registro_plan',
            'contexto' => ['registro' => []],
        ]);

        return 'Registro creado correctamente. '.$this->listaPlanes();
    }

    private function guardarPlan(Conversacion $conversacion, string $contenido): string
    {
        $idPlan = filter_var($contenido, FILTER_VALIDATE_INT);
        $plan = $idPlan === false ? null : Plan::query()->find($idPlan);
        if ($plan === null || $plan->estado !== EstadoPlan::Activo) {
            return 'El plan indicado no está disponible. '.$this->listaPlanes();
        }

        $this->actualizar($conversacion, 'registro_direccion_instalacion', ['id_plan' => $plan->id_plan]);

        return "Elegiste {$plan->nombre}. Indicá la dirección de instalación: calle | número | localidad.";
    }

    private function guardarDireccionInstalacion(Conversacion $conversacion, string $contenido): string
    {
        $direccion = $this->extraerDireccion($contenido);
        if ($direccion === null) {
            return 'Usá el formato calle | número | localidad para la instalación.';
        }

        $this->actualizar($conversacion, 'registro_dia_vencimiento', [
            'calle_instalacion' => $direccion['calle'],
            'numero_instalacion' => $direccion['numero'],
            'localidad_instalacion' => $direccion['localidad'],
        ]);

        return 'Por último, elegí el día de vencimiento mensual entre 1 y 28.';
    }

    private function crearServicio(Conversacion $conversacion, ?Cliente $cliente, string $contenido): string
    {
        $dia = filter_var($contenido, FILTER_VALIDATE_INT);
        if ($dia === false || $dia < 1 || $dia > 28) {
            return 'El día de vencimiento debe ser un número entre 1 y 28.';
        }

        $cliente ??= $conversacion->cliente;
        if ($cliente === null) {
            $conversacion->escalar();

            return 'No pude recuperar tu registro. Te transferimos con un operador.';
        }

        $datos = $this->datos($conversacion);
        $servicio = $this->servicioContrataciones->crear($cliente, [
            'id_plan' => $datos['id_plan'],
            'calle_instalacion' => $datos['calle_instalacion'],
            'numero_instalacion' => $datos['numero_instalacion'],
            'localidad_instalacion' => $datos['localidad_instalacion'],
            'dia_vencimiento' => $dia,
            'fecha_alta' => today()->toDateString(),
        ]);
        $conversacion->update(['paso_actual' => null, 'contexto' => null]);
        $conversacion->cerrar();

        return "La contratación quedó registrada como Servicio #{$servicio->id_servicio}. Un operador coordinará la instalación.";
    }

    /** @param array<string, mixed> $nuevos */
    private function actualizar(Conversacion $conversacion, string $paso, array $nuevos): void
    {
        $conversacion->update([
            'paso_actual' => $paso,
            'contexto' => ['registro' => array_merge($this->datos($conversacion), $nuevos)],
        ]);
    }

    /** @return array<string, mixed> */
    private function datos(Conversacion $conversacion): array
    {
        return (array) data_get($conversacion->contexto, 'registro', []);
    }

    /** @return array{calle: string, numero: ?string, localidad: string}|null */
    private function extraerDireccion(string $contenido): ?array
    {
        $partes = array_map('trim', explode('|', $contenido));
        if (count($partes) !== 3 || $partes[0] === '' || $partes[2] === '') {
            return null;
        }
        if (mb_strlen($partes[0]) > 100 || mb_strlen($partes[1]) > 10 || mb_strlen($partes[2]) > 100) {
            return null;
        }

        return [
            'calle' => $partes[0],
            'numero' => $partes[1] === '' || mb_strtolower($partes[1]) === 's/n' ? null : $partes[1],
            'localidad' => $partes[2],
        ];
    }

    private function listaPlanes(): string
    {
        $planes = Plan::query()->where('estado', EstadoPlan::Activo->value)->orderBy('precio_vigente')->get();
        if ($planes->isEmpty()) {
            return 'En este momento no hay planes disponibles. Escribí OPERADOR para recibir ayuda.';
        }

        $detalle = $planes->map(fn (Plan $plan): string => sprintf(
            '#%d %s (%s Mbps) $%s',
            $plan->id_plan,
            $plan->nombre,
            $plan->velocidad,
            number_format((float) $plan->precio_vigente, 2, ',', '.'),
        ))->implode(' · ');

        return "Planes disponibles: {$detalle}. Respondé con el número del plan elegido.";
    }
}
