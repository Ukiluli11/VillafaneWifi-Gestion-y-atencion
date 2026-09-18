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
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre_usuario', 50)->unique('uq_usuario_nombre');
            $table->string('credencial', 255);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
        });

        Schema::create('administrador', function (Blueprint $table) {
            $table->unsignedInteger('id_administrador')->primary();
            $table->enum('nivel_acceso', ['total', 'restringido'])->default('total');
            $table->boolean('puede_gestionar_usuarios')->default(true);
            $table->boolean('puede_configurar_planes')->default(true);
            $table->foreign('id_administrador', 'fk_administrador_usuario')->references('id_usuario')->on('usuario')->cascadeOnDelete();
        });

        Schema::create('empleado', function (Blueprint $table) {
            $table->unsignedInteger('id_empleado')->primary();
            $table->string('area', 100)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->enum('turno', ['mañana', 'tarde', 'noche'])->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->foreign('id_empleado', 'fk_empleado_usuario')->references('id_usuario')->on('usuario')->cascadeOnDelete();
        });

        Schema::create('cliente', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->enum('tipo_documento', ['DNI', 'CUIT', 'CUIL']);
            $table->string('numero_documento', 20);
            $table->string('nombre_razon_social', 150);
            $table->enum('tipo_cliente', ['particular', 'comercio'])->default('particular');
            $table->string('calle_contacto', 100)->nullable();
            $table->string('numero_contacto', 10)->nullable();
            $table->string('localidad_contacto', 100)->nullable();
            $table->string('telefono_whatsapp', 20)->nullable();
            $table->enum('estado', ['activo', 'suspendido', 'baja'])->default('activo');
            $table->unique(['tipo_documento', 'numero_documento'], 'uq_cliente_documento');
            $table->index(['nombre_razon_social', 'numero_documento'], 'ix_cliente_busqueda');
            $table->index('telefono_whatsapp', 'ix_cliente_whatsapp');
        });

        Schema::create('plan', function (Blueprint $table) {
            $table->increments('id_plan');
            $table->string('nombre', 100);
            $table->string('velocidad', 50);
            $table->decimal('precio_vigente', 10, 2);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
        });

        Schema::create('servicio', function (Blueprint $table) {
            $table->increments('id_servicio');
            $table->unsignedInteger('id_plan');
            $table->unsignedInteger('id_cliente');
            $table->string('calle_instalacion', 100);
            $table->string('numero_instalacion', 10)->nullable();
            $table->string('localidad_instalacion', 100);
            $table->unsignedTinyInteger('dia_vencimiento');
            $table->date('proximo_vencimiento')->nullable();
            $table->date('fecha_alta');
            $table->string('ipv4', 15)->nullable();
            $table->string('mac', 17)->nullable();
            $table->enum('estado', ['activo', 'suspendido', 'baja'])->default('activo');
            $table->foreign('id_cliente', 'fk_servicio_cliente')->references('id_cliente')->on('cliente')->restrictOnDelete();
            $table->foreign('id_plan', 'fk_servicio_plan')->references('id_plan')->on('plan')->restrictOnDelete();
            $table->index(['id_cliente', 'estado'], 'ix_servicio_cliente_estado');
        });

        Schema::create('cuenta_receptora', function (Blueprint $table) {
            $table->increments('id_cuenta');
            $table->string('nombre', 100);
            $table->enum('tipo', ['banco', 'mercado_pago', 'uala', 'otro']);
            $table->string('identificador', 100);
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->unique(['tipo', 'identificador'], 'uq_cuenta_identificador');
        });

        Schema::create('pago', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->unsignedInteger('id_comprobante')->nullable();
            $table->unsignedInteger('id_cuenta');
            $table->date('fecha');
            $table->decimal('monto_total', 10, 2);
            $table->enum('medio_pago', ['transferencia', 'efectivo', 'mercado_pago', 'uala', 'otro']);
            $table->foreign('id_cuenta', 'fk_pago_cuenta')->references('id_cuenta')->on('cuenta_receptora')->restrictOnDelete();
            $table->unique('id_comprobante', 'uq_pago_comprobante');
            $table->index(['id_cuenta', 'fecha'], 'ix_pago_cuenta_fecha');
        });

        Schema::create('cuota', function (Blueprint $table) {
            $table->increments('id_cuota');
            $table->unsignedInteger('id_servicio');
            $table->unsignedInteger('id_pago')->nullable();
            $table->char('periodo', 7);
            $table->decimal('monto', 10, 2);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['pendiente', 'pagada', 'vencida'])->default('pendiente');
            $table->foreign('id_servicio', 'fk_cuota_servicio')->references('id_servicio')->on('servicio')->restrictOnDelete();
            $table->foreign('id_pago', 'fk_cuota_pago')->references('id_pago')->on('pago')->restrictOnDelete();
            $table->unique(['id_servicio', 'periodo'], 'uq_cuota_servicio_periodo');
            $table->index(['estado', 'fecha_vencimiento'], 'ix_cuota_estado_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuota');
        Schema::dropIfExists('pago');
        Schema::dropIfExists('cuenta_receptora');
        Schema::dropIfExists('servicio');
        Schema::dropIfExists('plan');
        Schema::dropIfExists('cliente');
        Schema::dropIfExists('empleado');
        Schema::dropIfExists('administrador');
        Schema::dropIfExists('usuario');
    }
};
