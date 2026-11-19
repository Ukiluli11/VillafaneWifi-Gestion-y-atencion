<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega un indicador de aviso pendiente para el resultado de un comprobante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->boolean('aviso_resultado_pendiente')->default(false);
            $table->index(['id_cliente', 'aviso_resultado_pendiente'], 'comprobante_avisos_pendientes_idx');
        });
    }

    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->dropIndex('comprobante_avisos_pendientes_idx');
            $table->dropColumn('aviso_resultado_pendiente');
        });
    }
};
