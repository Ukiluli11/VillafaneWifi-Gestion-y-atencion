<?php

namespace App\Dominio;

use Illuminate\Support\Facades\Hash;


/**
 * Centraliza el hash de contraseñas, su verificación y la actualización del hash.
 */
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

    public function necesitaRehasheo(string $passwordHash): bool
    {
        return Hash::needsRehash($passwordHash);
    }
}
