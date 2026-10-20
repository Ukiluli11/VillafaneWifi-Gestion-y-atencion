<?php

namespace Database\Seeders;

use App\Enums\EstadoTicket;
use App\Models\Cliente;
use App\Models\Conversacion;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class TicketsDemostracionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Usuario::where('tipo', 'administrador')->first();
        $soporte = Usuario::whereHas('empleado', fn ($q) => $q->where('area', 'soporte'))->first() ?? $admin;

        $servicioAna = Servicio::whereHas('cliente', fn ($q) => $q->where('nombre_razon_social', 'like', '%Ana%'))->first()
            ?? Servicio::first();
        $servicioRoberto = Servicio::whereHas('cliente', fn ($q) => $q->where('nombre_razon_social', 'like', '%Roberto%'))->first()
            ?? Servicio::skip(1)->first() ?? $servicioAna;
        $servicioLapacho = Servicio::whereHas('cliente', fn ($q) => $q->where('nombre_razon_social', 'like', '%Lapacho%'))->first()
            ?? Servicio::skip(2)->first() ?? $servicioAna;

        if (! $servicioAna) {
            return;
        }

        $conversacionAna = Conversacion::where('id_cliente', $servicioAna->id_cliente)->first();
        $conversacionRoberto = Conversacion::where('id_cliente', $servicioRoberto->id_cliente)->first();

        // 1. Ticket Pendiente (reportado por bot o cliente)
        $t1 = Ticket::updateOrCreate(
            ['descripcion' => 'Cliente reporta luz LOS parpadeando en rojo y sin acceso a internet.'],
            [
                'id_conversacion' => $conversacionAna?->id_conversacion,
                'id_servicio' => $servicioAna->id_servicio,
                'id_usuario_asignado' => null,
                'fecha_creacion' => now()->subHours(5),
                'tipo' => 'tecnico',
                'estado' => EstadoTicket::Pendiente,
            ]
        );

        // 2. Ticket En Proceso (asignado a técnico)
        $t2 = Ticket::updateOrCreate(
            ['descripcion' => 'Microcortes reiterados en horas pico de la tarde. Pérdida de paquetes del 15%.'],
            [
                'id_conversacion' => $conversacionRoberto?->id_conversacion,
                'id_servicio' => $servicioRoberto->id_servicio,
                'id_usuario_asignado' => $soporte?->id_usuario,
                'fecha_creacion' => now()->subDays(1),
                'tipo' => 'tecnico',
                'estado' => EstadoTicket::EnProceso,
                'fecha_asignacion' => now()->subHours(18),
            ]
        );
        if ($soporte && $t2->notas()->count() === 0) {
            $t2->agregarNota('Revisión remota realizada desde la OLT. Niveles de señal óptica en -24 dBm.', $soporte);
            $t2->agregarNota('Se programa visita a domicilio para verificar cable drop.', $soporte);
        }

        // 3. Ticket Resuelto
        $t3 = Ticket::updateOrCreate(
            ['descripcion' => 'Cambio de domicilio y reinstalación de equipo ONU en nueva dirección comercial.'],
            [
                'id_conversacion' => null,
                'id_servicio' => $servicioLapacho->id_servicio,
                'id_usuario_asignado' => $soporte?->id_usuario,
                'fecha_creacion' => now()->subDays(3),
                'tipo' => 'instalacion',
                'estado' => EstadoTicket::Resuelto,
                'fecha_asignacion' => now()->subDays(2),
                'fecha_resolucion' => now()->subHours(6),
            ]
        );
        if ($soporte && $t3->notas()->count() === 0) {
            $t3->agregarNota('Instalación concluida. Potencia recibida: -19 dBm. Prueba de velocidad: 50 Mbps estables.', $soporte);
        }

        // 4. Ticket Reabierto (RF-31)
        $t4 = Ticket::updateOrCreate(
            ['descripcion' => 'Reclamo por degradación de velocidad contratada persistente los fines de semana.'],
            [
                'id_conversacion' => null,
                'id_servicio' => $servicioAna->id_servicio,
                'id_usuario_asignado' => $soporte?->id_usuario,
                'fecha_creacion' => now()->subDays(5),
                'tipo' => 'reclamo',
                'estado' => EstadoTicket::Reabierto,
                'fecha_asignacion' => now()->subDays(4),
                'fecha_resolucion' => null,
            ]
        );
        if ($soporte && $t4->notas()->count() === 0) {
            $t4->agregarNota('Ticket reabierto. Motivo: El cliente volvió a reportar caída de velocidad tras el cierre previo.', $soporte);
        }
    }
}
