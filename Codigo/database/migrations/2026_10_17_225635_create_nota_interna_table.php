<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_interna', function (Blueprint $table): void {
            $table->increments('id_nota');
            $table->unsignedInteger('id_ticket');
            $table->unsignedInteger('id_usuario');
            $table->dateTime('fecha_hora');
            $table->text('contenido');

            $table->foreign('id_ticket', 'fk_nota_ticket')
                ->references('id_ticket')->on('ticket')->cascadeOnDelete();
            $table->foreign('id_usuario', 'fk_nota_usuario')
                ->references('id_usuario')->on('usuario')->restrictOnDelete();
            $table->index(['id_ticket', 'fecha_hora'], 'ix_nota_ticket_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_interna');
    }
};
