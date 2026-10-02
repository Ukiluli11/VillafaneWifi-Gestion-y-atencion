<?php

namespace App\Dominio;

use Illuminate\Support\Facades\Hash;


class ServicioSeguridad
{
    
    public function hashearPassword(string $passwordPlano): string
    {
        return Hash::make($passwordPlano);
    }

    
    public function verificarPassword(string $passwordPlano, string $passwordHash): bool
    {
        return Hash::check($passwordPlano, $passwordHash);
    }

    /**
     * Determina si el hash almacenado requiere re-hasheo según la configuración vigente del sistema.
     */
    public function necesitaRehasheo(string $passwordHash): bool
    {
        return Hash::needsRehash($passwordHash);
    }
}
