<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los tickets originados en las conversaciones de WhatsApp.
     *
     * El empleado permanece sin asignar hasta que una persona tome el caso
     * desde la cola, tal como requiere la atención por orden de llegada.
     */
    public function up(): void
    {
        Schema::create('ticket', function (Blueprint $table) {
            $table->increments('id_ticket');
            $table->unsignedInteger('id_conversacion');
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_empleado')->nullable();
            $table->dateTime('fecha_creacion');
            $table->enum('tipo', ['tecnico', 'administrativo', 'consulta', 'otro'])->default('tecnico');
            $table->text('descripcion');
            $table->enum('estado', ['abierto', 'en_atencion', 'resuelto', 'cerrado'])->default('abierto');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->dateTime('fecha_asignacion')->nullable();
            $table->foreign('id_conversacion', 'fk_ticket_conversacion')->references('id_conversacion')->on('conversacion')->restrictOnDelete();
            $table->foreign('id_servicio', 'fk_ticket_servicio')->references('id_servicio')->on('servicio')->restrictOnDelete();
            $table->foreign('id_empleado', 'fk_ticket_empleado')->references('id_empleado')->on('empleado')->nullOnDelete();
            $table->index(['estado', 'fecha_creacion'], 'ix_ticket_cola');
            $table->index('id_conversacion', 'ix_ticket_conversacion');
            $table->index('id_servicio', 'ix_ticket_servicio');
        });
    }

    /** Elimina la tabla de tickets al revertir este incremento. */
    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
