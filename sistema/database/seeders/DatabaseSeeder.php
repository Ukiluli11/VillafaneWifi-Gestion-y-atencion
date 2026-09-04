<?php

namespace Database\Seeders;

use App\Dominio\ServicioUsuarios;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $nombreUsuario = config('administracion.usuario_inicial');
        $contrasena = config('administracion.contrasena_inicial');

        if (! is_string($nombreUsuario) || $nombreUsuario === '' || ! is_string($contrasena) || $contrasena === '') {
            $this->command?->warn('No se creó el administrador inicial: configurá ADMIN_INICIAL_USUARIO y ADMIN_INICIAL_CONTRASENA.');
        } elseif (! Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
            app(ServicioUsuarios::class)->crear([
                'nombre_usuario' => $nombreUsuario,
                'contrasena' => $contrasena,
                'tipo' => 'administrador',
                'nivel_acceso' => 'total',
                'puede_gestionar_usuarios' => true,
                'puede_configurar_planes' => true,
            ]);
        }

        $this->call(DatosDemostracionSeeder::class);
    }
}
