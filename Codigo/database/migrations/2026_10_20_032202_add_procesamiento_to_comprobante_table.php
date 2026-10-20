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
        Schema::table('comprobante', function (Blueprint $table) {
            $table->string('disco_archivo', 50)->default('local');
            $table->string('estado_ocr', 30)->default('sin_procesar');
            $table->string('error_ocr')->nullable();
            $table->decimal('completitud_ocr', 3, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table) {
            $table->dropColumn(['disco_archivo', 'estado_ocr', 'error_ocr', 'completitud_ocr']);
        });
    }
};
