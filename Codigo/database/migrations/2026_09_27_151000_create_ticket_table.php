<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket', function (Blueprint $table) {
            $table->increments('id_ticket');
            $table->unsignedInteger('id_conversacion');
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_usuario_asignado')->nullable();
            $table->dateTime('fecha_creacion');
            $table->string('tipo', 50)->default('tecnico');
            $table->text('descripcion');
            $table->enum('estado', ['pendiente', 'asignado', 'en_proceso', 'resuelto', 'cerrado', 'reabierto'])
                ->default('pendiente');
            $table->dateTime('fecha_asignacion')->nullable();
            $table->dateTime('fecha_resolucion')->nullable();

            $table->foreign('id_conversacion', 'fk_ticket_conversacion')
                ->references('id_conversacion')->on('conversacion')->restrictOnDelete();
            $table->foreign('id_servicio', 'fk_ticket_servicio')
                ->references('id_servicio')->on('servicio')->restrictOnDelete();
            $table->foreign('id_usuario_asignado', 'fk_ticket_usuario')
                ->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->index(['estado', 'fecha_creacion'], 'ix_ticket_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};
