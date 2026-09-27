<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->unsignedInteger('id_conversacion')->nullable()->after('id_cliente');
            $table->unsignedInteger('id_mensaje')->nullable()->after('id_conversacion');
            $table->dateTime('fecha_recepcion')->nullable()->after('id_mensaje');
            $table->string('nombre_original', 255)->nullable()->after('fecha_recepcion');
            $table->string('mime_type', 100)->nullable()->after('nombre_original');
            $table->unsignedBigInteger('tamanio_bytes')->nullable()->after('mime_type');

            $table->foreign('id_conversacion', 'fk_comprobante_conversacion')
                ->references('id_conversacion')->on('conversacion')->nullOnDelete();
            $table->foreign('id_mensaje', 'fk_comprobante_mensaje')
                ->references('id_mensaje')->on('mensaje')->nullOnDelete();
            $table->unique('id_mensaje', 'uq_comprobante_mensaje');
            $table->index(['id_cliente', 'fecha_recepcion'], 'ix_comprobante_cliente_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->dropForeign('fk_comprobante_conversacion');
            $table->dropForeign('fk_comprobante_mensaje');
            $table->dropUnique('uq_comprobante_mensaje');
            $table->dropIndex('ix_comprobante_cliente_fecha');
            $table->dropColumn([
                'id_conversacion', 'id_mensaje', 'fecha_recepcion',
                'nombre_original', 'mime_type', 'tamanio_bytes',
            ]);
        });
    }
};
