<?php

namespace App\Dominio;

use Illuminate\Support\Facades\Hash;

/**
 * Servicio de Seguridad y Criptografía del Dominio.
 * Encapsula las operaciones de hashing y validación de contraseñas de usuarios (RF-40, RF-41, RF-42),
 * reflejando fielmente la línea de vida "ServicioSeguridad" modelada en el Diagrama de Secuencia del Módulo 6 (UML).
 */
class ServicioSeguridad
{
    /**
     * Genera el hash criptográfico seguro (Bcrypt / Argon2) para la contraseña en texto plano.
     */
    public function hashearPassword(string $passwordPlano): string
    {
        return Hash::make($passwordPlano);
    }

    /**
     * Verifica si una contraseña en texto plano coincide con el hash almacenado.
     */
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
