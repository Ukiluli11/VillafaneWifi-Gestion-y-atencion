<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario')->nullable()->after('id_pago');
            $table->dateTime('fecha_hora_validacion')->nullable()->after('motivo_rechazo');

            $table->foreign('id_usuario', 'fk_comprobante_usuario')
                ->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->index('fecha_hora_validacion', 'ix_comprobante_fecha_validacion');
        });
    }

    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->dropForeign('fk_comprobante_usuario');
            $table->dropIndex('ix_comprobante_fecha_validacion');
            $table->dropColumn(['id_usuario', 'fecha_hora_validacion']);
        });
    }
};
