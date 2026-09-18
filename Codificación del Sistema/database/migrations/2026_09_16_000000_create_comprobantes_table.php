<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Crea la persistencia de comprobantes y el estado del flujo del bot. */
    public function up(): void
    {
        Schema::table('conversacion', function (Blueprint $table) {
            $table->string('estado_flujo', 50)->default('menu')->after('modo_atencion');
            $table->index('estado_flujo', 'ix_conversacion_estado_flujo');
        });

        Schema::create('comprobante', function (Blueprint $table) {
            $table->increments('id_comprobante');
            $table->unsignedInteger('id_mensaje');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->char('hash_archivo', 64)->nullable();
            $table->dateTime('fecha_recepcion');
            $table->string('numero_operacion', 100)->nullable();
            $table->decimal('monto_ocr', 12, 2)->nullable();
            $table->date('fecha_ocr')->nullable();
            $table->decimal('confianza_ocr', 5, 2)->nullable();
            $table->enum('estado_validacion', ['pendiente', 'aprobado', 'rechazado', 'duplicado'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->dateTime('fecha_hora_validacion')->nullable();
            $table->foreign('id_mensaje', 'fk_comprobante_mensaje')->references('id_mensaje')->on('mensaje')->restrictOnDelete();
            $table->foreign('id_usuario', 'fk_comprobante_usuario')->references('id_usuario')->on('usuario')->restrictOnDelete();
            $table->unique('id_mensaje', 'uq_comprobante_mensaje');
            $table->unique('hash_archivo', 'uq_comprobante_hash');
            $table->unique('numero_operacion', 'uq_comprobante_operacion');
            $table->index(['estado_validacion', 'fecha_recepcion'], 'ix_comprobante_cola');
        });

        Schema::table('pago', function (Blueprint $table) {
            $table->foreign('id_comprobante', 'fk_pago_comprobante')
                ->references('id_comprobante')->on('comprobante')->restrictOnDelete();
        });
    }

    /** Revierte primero las referencias que dependen del comprobante. */
    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropForeign('fk_pago_comprobante');
        });
        Schema::dropIfExists('comprobante');
        Schema::table('conversacion', function (Blueprint $table) {
            $table->dropIndex('ix_conversacion_estado_flujo');
            $table->dropColumn('estado_flujo');
        });
    }
};
