<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conversacion')) {
            Schema::create('conversacion', function (Blueprint $table) {
                $table->increments('id_conversacion');
                $table->unsignedInteger('id_cliente')->nullable();
                $table->unsignedInteger('id_usuario_atencion')->nullable();
                $table->string('numero_whatsapp', 20);
                $table->dateTime('fecha_hora_inicio');
                $table->dateTime('fecha_hora_cierre')->nullable();
                $table->enum('estado', ['abierta', 'escalada', 'en_atencion', 'cerrada'])->default('abierta');
                $table->enum('modo_atencion', ['bot', 'humano'])->default('bot');
                $table->string('intencion_actual', 50)->nullable();
                $table->string('paso_actual', 80)->nullable();
                $table->json('contexto')->nullable();
                $table->unsignedTinyInteger('intentos_interpretacion')->default(0);
                $table->dateTime('inicio_atencion')->nullable();
                $table->dateTime('fin_atencion')->nullable();

                $table->foreign('id_cliente', 'fk_conversacion_cliente')
                    ->references('id_cliente')->on('cliente')->nullOnDelete();
                $table->foreign('id_usuario_atencion', 'fk_conversacion_usuario_atencion')
                    ->references('id_usuario')->on('usuario')->nullOnDelete();
                $table->index(['numero_whatsapp', 'estado'], 'ix_conversacion_numero_estado');
                $table->index(['estado', 'fecha_hora_inicio'], 'ix_conversacion_estado_inicio');
            });
        }

        if (! Schema::hasTable('mensaje')) {
            Schema::create('mensaje', function (Blueprint $table) {
                $table->increments('id_mensaje');
                $table->unsignedInteger('id_conversacion');
                $table->unsignedInteger('id_usuario')->nullable();
                $table->string('id_mensaje_externo', 120)->nullable()->unique('uq_mensaje_externo');
                $table->string('clave_idempotencia', 150)->nullable()->unique('uq_mensaje_idempotencia');
                $table->dateTime('fecha_hora');
                $table->enum('tipo', [
                    'texto', 'imagen', 'documento', 'audio', 'ubicacion', 'contacto',
                    'interactivo', 'notificacion', 'desconocido',
                ])->default('texto');
                $table->text('contenido')->nullable();
                $table->string('archivo_adjunto', 255)->nullable();
                $table->enum('tipo_emisor', ['cliente', 'bot', 'usuario_interno', 'sistema']);
                $table->enum('estado_envio', ['recibido', 'pendiente', 'enviado', 'entregado', 'leido', 'fallido']);

                $table->foreign('id_conversacion', 'fk_mensaje_conversacion')
                    ->references('id_conversacion')->on('conversacion')->cascadeOnDelete();
                $table->foreign('id_usuario', 'fk_mensaje_usuario')
                    ->references('id_usuario')->on('usuario')->nullOnDelete();
                $table->index(['id_conversacion', 'fecha_hora'], 'ix_mensaje_conversacion_fecha');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mensaje');
        Schema::dropIfExists('conversacion');
    }
};
