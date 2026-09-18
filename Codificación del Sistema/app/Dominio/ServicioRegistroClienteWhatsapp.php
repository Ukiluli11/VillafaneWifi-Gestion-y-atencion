<?php

namespace App\Dominio;

use App\Enums\EstadoCliente;
use App\Enums\EstadoFlujoWhatsapp;
use App\Enums\EstadoPlan;
use App\Enums\EstadoServicio;
use App\Enums\TipoCliente;
use App\Enums\TipoDocumento;
use App\Enums\TipoMensaje;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

/**
 * Conduce la identificación o el alta de un cliente desde WhatsApp.
 *
 * Los datos parciales viven en la conversación hasta que el flujo termina;
 * recién entonces se crean juntos el cliente y su primer servicio.
 */
class ServicioRegistroClienteWhatsapp
{
    public function __construct(
        private readonly ServicioEnvioWhatsapp $envio,
        private readonly ServicioAtencionInicialWhatsapp $atencionInicial,
        private readonly ServicioClientes $clientes,
        private readonly ServicioContrataciones $contrataciones,
    ) {}

    /** Solicita el documento al recibir el primer contacto desconocido. */
    public function iniciar(Conversacion $conversacion): Mensaje
    {
        $conversacion->update([
            'estado_flujo' => EstadoFlujoWhatsapp::EsperandoDocumento,
            'datos_registro' => null,
        ]);

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'No encontramos un cliente asociado a este número. '
            .'Escribí tu DNI, sin puntos, para identificarte o comenzar el registro.',
        );
    }

    /** Procesa el dato esperado por la etapa actual del registro. */
    public function procesar(Conversacion $conversacion, Mensaje $mensaje): Mensaje
    {
        if ($mensaje->tipo !== TipoMensaje::Texto || blank($mensaje->contenido)) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'Para continuar el registro necesito que respondas con el dato solicitado en un mensaje de texto.',
            );
        }

        return match ($conversacion->estado_flujo) {
            EstadoFlujoWhatsapp::EsperandoDocumento => $this->procesarDocumento($conversacion, $mensaje->contenido),
            EstadoFlujoWhatsapp::EsperandoNombreRegistro => $this->procesarNombre($conversacion, $mensaje->contenido),
            EstadoFlujoWhatsapp::EsperandoDireccionContacto => $this->procesarDireccionContacto($conversacion, $mensaje->contenido),
            EstadoFlujoWhatsapp::EsperandoPlanRegistro => $this->procesarPlan($conversacion, $mensaje->contenido),
            EstadoFlujoWhatsapp::EsperandoDireccionInstalacion => $this->procesarDireccionInstalacion($conversacion, $mensaje->contenido),
            EstadoFlujoWhatsapp::EsperandoDiaVencimiento => $this->procesarDiaVencimiento($conversacion, $mensaje->contenido),
            default => $this->iniciar($conversacion),
        };
    }

    private function procesarDocumento(Conversacion $conversacion, string $contenido): Mensaje
    {
        $documento = preg_replace('/\D+/', '', $contenido) ?? '';
        if (! in_array(strlen($documento), [7, 8], true)) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'El DNI debe tener 7 u 8 números. Volvé a escribirlo sin puntos ni espacios.',
            );
        }

        $cliente = Cliente::query()->where('numero_documento', $documento)->first();
        if ($cliente !== null) {
            return $this->vincularClienteExistente($conversacion, $cliente);
        }

        $this->avanzar($conversacion, EstadoFlujoWhatsapp::EsperandoNombreRegistro, [
            'tipo_documento' => TipoDocumento::Dni->value,
            'numero_documento' => $documento,
        ]);

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'Ese DNI no está registrado. Para darte de alta, escribí tu nombre y apellido completos.',
        );
    }

    private function vincularClienteExistente(Conversacion $conversacion, Cliente $cliente): Mensaje
    {
        DB::transaction(function () use ($conversacion, $cliente): void {
            $this->clientes->actualizar($cliente, ['telefono_whatsapp' => $conversacion->numero_whatsapp]);
            $conversacion->update([
                'id_cliente' => $cliente->id_cliente,
                'estado_flujo' => EstadoFlujoWhatsapp::Menu,
                'datos_registro' => null,
                'intentos_intencion' => 0,
            ]);
        });

        return $this->atencionInicial->saludarCliente($conversacion->refresh(), $cliente->refresh());
    }

    private function procesarNombre(Conversacion $conversacion, string $contenido): Mensaje
    {
        $nombre = preg_replace('/\s+/', ' ', trim($contenido)) ?? '';
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'Ingresá un nombre y apellido válidos, de entre 3 y 150 caracteres.',
            );
        }

        $this->avanzar($conversacion, EstadoFlujoWhatsapp::EsperandoDireccionContacto, [
            'nombre_razon_social' => $nombre,
        ]);

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'Indicá tu domicilio de contacto con este formato: calle | número | localidad. Ejemplo: Belgrano | 123 | Formosa.',
        );
    }

    private function procesarDireccionContacto(Conversacion $conversacion, string $contenido): Mensaje
    {
        $direccion = $this->interpretarDireccion($contenido);
        if ($direccion === null) {
            return $this->mensajeDireccionInvalida($conversacion);
        }

        $this->avanzar($conversacion, EstadoFlujoWhatsapp::EsperandoPlanRegistro, [
            'calle_contacto' => $direccion['calle'],
            'numero_contacto' => $direccion['numero'],
            'localidad_contacto' => $direccion['localidad'],
        ]);

        $planes = Plan::query()->where('estado', EstadoPlan::Activo->value)->orderBy('id_plan')->get();
        if ($planes->isEmpty()) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'En este momento no hay planes disponibles. Solicitá atención humana para que podamos continuar el alta.',
            );
        }

        $opciones = $planes
            ->map(fn (Plan $plan): string => "{$plan->id_plan}. {$plan->nombre} - {$plan->velocidad} - $".number_format((float) $plan->precio_vigente, 2, ',', '.'))
            ->implode("\n");

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            "Elegí el plan escribiendo su número:\n{$opciones}",
        );
    }

    private function procesarPlan(Conversacion $conversacion, string $contenido): Mensaje
    {
        $identificadorPlan = filter_var(trim($contenido), FILTER_VALIDATE_INT);
        $plan = $identificadorPlan === false
            ? null
            : Plan::query()->whereKey($identificadorPlan)->where('estado', EstadoPlan::Activo->value)->first();

        if ($plan === null) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'La opción no corresponde a un plan activo. Escribí uno de los números mostrados.',
            );
        }

        $this->avanzar($conversacion, EstadoFlujoWhatsapp::EsperandoDireccionInstalacion, [
            'id_plan' => $plan->id_plan,
        ]);

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'Indicá el domicilio donde se instalará el servicio: calle | número | localidad.',
        );
    }

    private function procesarDireccionInstalacion(Conversacion $conversacion, string $contenido): Mensaje
    {
        $direccion = $this->interpretarDireccion($contenido);
        if ($direccion === null) {
            return $this->mensajeDireccionInvalida($conversacion);
        }

        $this->avanzar($conversacion, EstadoFlujoWhatsapp::EsperandoDiaVencimiento, [
            'calle_instalacion' => $direccion['calle'],
            'numero_instalacion' => $direccion['numero'],
            'localidad_instalacion' => $direccion['localidad'],
        ]);

        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'Elegí el día de vencimiento mensual escribiendo un número del 1 al 28.',
        );
    }

    private function procesarDiaVencimiento(Conversacion $conversacion, string $contenido): Mensaje
    {
        $dia = filter_var(trim($contenido), FILTER_VALIDATE_INT);
        if ($dia === false || $dia < 1 || $dia > 28) {
            return $this->envio->enviarTextoDelBot(
                $conversacion,
                'El día de vencimiento debe ser un número entre 1 y 28.',
            );
        }

        $datos = [...($conversacion->datos_registro ?? []), 'dia_vencimiento' => $dia];
        $cliente = DB::transaction(function () use ($conversacion, $datos): Cliente {
            $cliente = $this->clientes->crear([
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_documento'],
                'nombre_razon_social' => $datos['nombre_razon_social'],
                'tipo_cliente' => TipoCliente::Particular,
                'calle_contacto' => $datos['calle_contacto'],
                'numero_contacto' => $datos['numero_contacto'],
                'localidad_contacto' => $datos['localidad_contacto'],
                'telefono_whatsapp' => $conversacion->numero_whatsapp,
                'estado' => EstadoCliente::Activo,
            ]);

            $this->contrataciones->crear($cliente, [
                'id_plan' => $datos['id_plan'],
                'calle_instalacion' => $datos['calle_instalacion'],
                'numero_instalacion' => $datos['numero_instalacion'],
                'localidad_instalacion' => $datos['localidad_instalacion'],
                'dia_vencimiento' => $datos['dia_vencimiento'],
                'fecha_alta' => today(),
                'ipv4' => null,
                'mac' => null,
                'estado' => EstadoServicio::Activo,
            ]);

            $conversacion->update([
                'id_cliente' => $cliente->id_cliente,
                'estado_flujo' => EstadoFlujoWhatsapp::Menu,
                'datos_registro' => null,
                'intentos_intencion' => 0,
            ]);

            return $cliente;
        });

        return $this->atencionInicial->confirmarRegistro($conversacion->refresh(), $cliente);
    }

    /** @param array<string, mixed> $datos */
    private function avanzar(Conversacion $conversacion, EstadoFlujoWhatsapp $estado, array $datos): void
    {
        $conversacion->update([
            'estado_flujo' => $estado,
            'datos_registro' => [...($conversacion->datos_registro ?? []), ...$datos],
        ]);
        $conversacion->refresh();
    }

    /** @return array{calle: string, numero: string|null, localidad: string}|null */
    private function interpretarDireccion(string $contenido): ?array
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
            'numero' => in_array(mb_strtolower($partes[1]), ['', 's/n', 'sn'], true) ? null : $partes[1],
            'localidad' => $partes[2],
        ];
    }

    private function mensajeDireccionInvalida(Conversacion $conversacion): Mensaje
    {
        return $this->envio->enviarTextoDelBot(
            $conversacion,
            'No pude interpretar la dirección. Usá el formato: calle | número | localidad. Podés escribir s/n si no tiene número.',
        );
    }
}
