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
        Schema::table('conversacion', function (Blueprint $table) {
            $table->unsignedTinyInteger('intentos_intencion')->default(0)->after('estado_flujo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversacion', function (Blueprint $table) {
            $table->dropColumn('intentos_intencion');
        });
    }
};
