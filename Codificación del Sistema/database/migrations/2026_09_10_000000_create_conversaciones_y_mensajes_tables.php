<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la persistencia base del módulo de atención por WhatsApp.
     */
    public function up(): void
    {
        Schema::create('conversacion', function (Blueprint $table) {
            $table->increments('id_conversacion');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_usuario_atencion')->nullable();
            $table->string('numero_whatsapp', 20);
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_cierre')->nullable();
            $table->enum('estado', ['abierta', 'escalada', 'cerrada'])->default('abierta');
            $table->enum('modo_atencion', ['bot', 'usuario_interno'])->default('bot');
            $table->dateTime('inicio_atencion')->nullable();
            $table->dateTime('fin_atencion')->nullable();
            $table->foreign('id_cliente', 'fk_conversacion_cliente')->references('id_cliente')->on('cliente')->restrictOnDelete();
            $table->foreign('id_usuario_atencion', 'fk_conversacion_usuario_atencion')->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->index(['id_cliente', 'estado'], 'ix_conversacion_cliente_estado');
            $table->index(['estado', 'fecha_hora_inicio'], 'ix_conversacion_cola');
            $table->index('numero_whatsapp', 'ix_conversacion_whatsapp');
        });

        Schema::create('mensaje', function (Blueprint $table) {
            $table->increments('id_mensaje');
            $table->unsignedInteger('id_conversacion');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('id_mensaje_externo', 100)->nullable();
            $table->dateTime('fecha_hora');
            $table->enum('tipo', ['texto', 'imagen', 'audio', 'documento']);
            $table->text('contenido')->nullable();
            $table->string('archivo_adjunto', 500)->nullable();
            $table->enum('tipo_emisor', ['cliente', 'bot', 'usuario_interno']);
            $table->enum('estado_envio', ['recibido', 'pendiente', 'enviado', 'entregado', 'leido', 'fallido']);
            $table->foreign('id_conversacion', 'fk_mensaje_conversacion')->references('id_conversacion')->on('conversacion')->cascadeOnDelete();
            $table->foreign('id_usuario', 'fk_mensaje_usuario')->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->unique('id_mensaje_externo', 'uq_mensaje_externo');
            $table->index(['id_conversacion', 'fecha_hora'], 'ix_mensaje_conversacion_fecha');
            $table->index('estado_envio', 'ix_mensaje_estado_envio');
        });
    }

    /**
     * Elimina primero la entidad dependiente para respetar las claves foráneas.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensaje');
        Schema::dropIfExists('conversacion');
    }
};
