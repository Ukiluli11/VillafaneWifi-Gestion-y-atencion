<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega información para alertar sobre comprobantes posiblemente duplicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobante', function (Blueprint $table): void {
            $table->unsignedInteger('intentos_duplicados')->default(0);
            $table->dateTime('ultimo_duplicado_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('comprobante', function (Blueprint $table): void {
            $table->dropColumn(['intentos_duplicados', 'ultimo_duplicado_at']);
        });
    }
};
