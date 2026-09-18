<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aviso_vencimiento', function (Blueprint $table) {
            $table->increments('id_aviso_vencimiento');
            $table->unsignedInteger('id_cuota');
            $table->unsignedInteger('id_conversacion')->nullable();
            $table->unsignedInteger('id_mensaje')->nullable();
            $table->enum('tipo', ['proximo', 'vencido']);
            $table->enum('estado', ['pendiente', 'enviado', 'fallido'])->default('pendiente');
            $table->dateTime('fecha_hora_ultimo_intento')->nullable();
            $table->dateTime('fecha_hora_envio')->nullable();
            $table->text('detalle_error')->nullable();
            $table->foreign('id_cuota', 'fk_aviso_vencimiento_cuota')->references('id_cuota')->on('cuota')->restrictOnDelete();
            $table->foreign('id_conversacion', 'fk_aviso_vencimiento_conversacion')->references('id_conversacion')->on('conversacion')->nullOnDelete();
            $table->foreign('id_mensaje', 'fk_aviso_vencimiento_mensaje')->references('id_mensaje')->on('mensaje')->nullOnDelete();
            $table->unique(['id_cuota', 'tipo'], 'uq_aviso_vencimiento_cuota_tipo');
            $table->index(['estado', 'fecha_hora_ultimo_intento'], 'ix_aviso_vencimiento_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviso_vencimiento');
    }
};
