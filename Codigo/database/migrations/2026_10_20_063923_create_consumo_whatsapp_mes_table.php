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
        Schema::create('consumo_whatsapp_mes', function (Blueprint $table) {
            $table->string('id_telefono', 80);
            $table->char('periodo', 7);
            $table->unsignedInteger('servicios_reservados')->default(0);
            $table->unsignedInteger('plantillas_reservadas')->default(0);
            $table->date('dia_plantillas')->nullable();
            $table->unsignedInteger('plantillas_del_dia')->default(0);
            $table->primary(['id_telefono', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumo_whatsapp_mes');
    }
};
