<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket', function (Blueprint $table): void {
            $table->unsignedInteger('id_conversacion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('ticket')->whereNull('id_conversacion')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan tickets manuales sin conversación.');
        }
        Schema::table('ticket', function (Blueprint $table): void {
            $table->unsignedInteger('id_conversacion')->nullable(false)->change();
        });
    }
};
