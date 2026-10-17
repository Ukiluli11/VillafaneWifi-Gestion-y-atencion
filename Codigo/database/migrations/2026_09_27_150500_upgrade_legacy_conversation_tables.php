<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->agregarColumnasConversacion();
        $this->agregarColumnasMensaje();

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->actualizarEnumsMariaDb();
        }

        if (! Schema::hasIndex('conversacion', 'ix_conversacion_numero_estado')) {
            Schema::table('conversacion', function (Blueprint $table) {
                $table->index(['numero_whatsapp', 'estado'], 'ix_conversacion_numero_estado');
            });
        }

        if (! Schema::hasIndex('mensaje', 'uq_mensaje_idempotencia')) {
            Schema::table('mensaje', function (Blueprint $table) {
                $table->unique('clave_idempotencia', 'uq_mensaje_idempotencia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('conversacion', 'ix_conversacion_numero_estado')) {
            Schema::table('conversacion', fn (Blueprint $table) => $table
                ->dropIndex('ix_conversacion_numero_estado'));
        }

        if (Schema::hasIndex('mensaje', 'uq_mensaje_idempotencia')) {
            Schema::table('mensaje', fn (Blueprint $table) => $table
                ->dropUnique('uq_mensaje_idempotencia'));
        }

        Schema::table('mensaje', function (Blueprint $table) {
            foreach (['clave_idempotencia'] as $columna) {
                if (Schema::hasColumn('mensaje', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });

        Schema::table('conversacion', function (Blueprint $table) {
            foreach (['intencion_actual', 'paso_actual', 'contexto', 'intentos_interpretacion'] as $columna) {
                if (Schema::hasColumn('conversacion', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    private function agregarColumnasConversacion(): void
    {
        $faltan = [
            'intencion_actual' => ! Schema::hasColumn('conversacion', 'intencion_actual'),
            'paso_actual' => ! Schema::hasColumn('conversacion', 'paso_actual'),
            'contexto' => ! Schema::hasColumn('conversacion', 'contexto'),
            'intentos_interpretacion' => ! Schema::hasColumn('conversacion', 'intentos_interpretacion'),
        ];

        Schema::table('conversacion', function (Blueprint $table) use ($faltan) {
            if ($faltan['intencion_actual']) {
                $table->string('intencion_actual', 50)->nullable()->after('modo_atencion');
            }
            if ($faltan['paso_actual']) {
                $table->string('paso_actual', 80)->nullable()->after('intencion_actual');
            }
            if ($faltan['contexto']) {
                $table->json('contexto')->nullable()->after('paso_actual');
            }
            if ($faltan['intentos_interpretacion']) {
                $table->unsignedTinyInteger('intentos_interpretacion')->default(0)->after('contexto');
            }
        });
    }

    private function agregarColumnasMensaje(): void
    {
        if (! Schema::hasColumn('mensaje', 'clave_idempotencia')) {
            Schema::table('mensaje', function (Blueprint $table) {
                $table->string('clave_idempotencia', 150)->nullable()->after('id_mensaje_externo');
            });
        }
    }

    private function actualizarEnumsMariaDb(): void
    {
        DB::statement('ALTER TABLE conversacion MODIFY id_cliente INT UNSIGNED NULL');
        DB::statement("ALTER TABLE conversacion MODIFY estado ENUM('abierta','escalada','en_atencion','cerrada') NOT NULL DEFAULT 'abierta'");
        DB::statement("ALTER TABLE conversacion MODIFY modo_atencion ENUM('bot','usuario_interno','humano') NOT NULL DEFAULT 'bot'");
        DB::table('conversacion')->where('modo_atencion', 'usuario_interno')->update(['modo_atencion' => 'humano']);
        DB::statement("ALTER TABLE conversacion MODIFY modo_atencion ENUM('bot','humano') NOT NULL DEFAULT 'bot'");

        DB::statement('ALTER TABLE mensaje MODIFY id_mensaje_externo VARCHAR(120) NULL');
        DB::statement("ALTER TABLE mensaje MODIFY tipo ENUM('texto','imagen','documento','audio','ubicacion','contacto','interactivo','notificacion','desconocido') NOT NULL DEFAULT 'texto'");
        DB::statement("ALTER TABLE mensaje MODIFY tipo_emisor ENUM('cliente','bot','usuario_interno','sistema') NOT NULL");
    }
};
