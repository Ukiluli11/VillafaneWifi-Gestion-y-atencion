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
        Schema::create('comprobante', function (Blueprint $table) {
            $table->increments('id_comprobante');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_pago')->nullable();
            $table->string('hash_archivo', 64)->unique('uq_comprobante_hash');
            $table->string('numero_operacion', 50)->nullable()->index('ix_comprobante_operacion');
            $table->decimal('monto_ocr', 10, 2)->nullable();
            $table->date('fecha_ocr')->nullable();
            $table->string('ruta_archivo', 255)->nullable();
            $table->enum('estado_validacion', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente')->index('ix_comprobante_estado');
            $table->string('motivo_rechazo', 255)->nullable();
            $table->enum('origen', ['whatsapp', 'panel_manual'])->default('panel_manual');
            $table->timestamps();

            $table->foreign('id_cliente', 'fk_comprobante_cliente')->references('id_cliente')->on('cliente')->cascadeOnDelete();
            $table->foreign('id_pago', 'fk_comprobante_pago')->references('id_pago')->on('pago')->nullOnDelete();
        });

        Schema::table('pago', function (Blueprint $table) {
            $table->foreign('id_comprobante', 'fk_pago_comprobante')->references('id_comprobante')->on('comprobante')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropForeign('fk_pago_comprobante');
        });

        Schema::dropIfExists('comprobante');
    }
};
