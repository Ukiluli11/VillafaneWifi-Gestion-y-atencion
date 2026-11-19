<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega un indicador para identificar comprobantes cuyo reenvío por OCR está pendiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante', function (Blueprint $table): void {
            $table->boolean('reenvio_ocr_pendiente')->default(false);
        });

        DB::table('comprobante')->where('origen', 'whatsapp')->where('estado_validacion', 'pendiente')
            ->whereIn('estado_ocr', ['procesado', 'fallido'])->whereNotNull('id_mensaje')
            ->where(fn ($consulta) => $consulta->whereNull('monto_ocr')->orWhereNull('fecha_ocr'))
            ->update(['reenvio_ocr_pendiente' => true]);
    }

    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table): void {
            $table->dropColumn('reenvio_ocr_pendiente');
        });
    }
};
