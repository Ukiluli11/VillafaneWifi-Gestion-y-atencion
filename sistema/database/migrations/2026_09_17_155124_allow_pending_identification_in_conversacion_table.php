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
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('conversacion', function (Blueprint $table) {
                $table->unsignedInteger('id_cliente')->nullable()->change();
                $table->json('datos_registro')->nullable()->after('intentos_intencion');
            });

            return;
        }

        Schema::table('conversacion', function (Blueprint $table) {
            $table->dropForeign('fk_conversacion_cliente');
            $table->unsignedInteger('id_cliente')->nullable()->change();
            $table->json('datos_registro')->nullable()->after('intentos_intencion');
            $table->foreign('id_cliente', 'fk_conversacion_cliente')
                ->references('id_cliente')
                ->on('cliente')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /* Las conversaciones que no terminaron el alta no son compatibles con el esquema anterior. */
        DB::table('conversacion')->whereNull('id_cliente')->delete();

        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('conversacion', function (Blueprint $table) {
                $table->dropColumn('datos_registro');
                $table->unsignedInteger('id_cliente')->nullable(false)->change();
            });

            return;
        }

        Schema::table('conversacion', function (Blueprint $table) {
            $table->dropForeign('fk_conversacion_cliente');
            $table->dropColumn('datos_registro');
            $table->unsignedInteger('id_cliente')->nullable(false)->change();
            $table->foreign('id_cliente', 'fk_conversacion_cliente')
                ->references('id_cliente')
                ->on('cliente')
                ->restrictOnDelete();
        });
    }
};
