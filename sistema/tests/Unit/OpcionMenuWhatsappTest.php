<?php

namespace Tests\Unit;

use App\Enums\OpcionMenuWhatsapp;
use PHPUnit\Framework\TestCase;

/** Verifica la interpretación inicial de las opciones escritas por el cliente. */
class OpcionMenuWhatsappTest extends TestCase
{
    /** Reconoce tanto el número como frases habituales para consultar deuda. */
    public function test_interpreta_la_consulta_de_estado_de_cuenta(): void
    {
        $this->assertSame(
            OpcionMenuWhatsapp::ConsultarEstadoCuenta,
            OpcionMenuWhatsapp::desdeMensaje('1'),
        );
        $this->assertSame(
            OpcionMenuWhatsapp::ConsultarEstadoCuenta,
            OpcionMenuWhatsapp::desdeMensaje('Quiero consultar mi deuda'),
        );
        $this->assertSame(
            OpcionMenuWhatsapp::ConsultarEstadoCuenta,
            OpcionMenuWhatsapp::desdeMensaje('Estado de cuenta'),
        );
    }

    /** No fuerza una intención cuando el texto no coincide con el menú. */
    public function test_ignora_un_texto_sin_una_opcion_reconocible(): void
    {
        $this->assertNull(OpcionMenuWhatsapp::desdeMensaje('Necesito ayuda con otra cosa'));
    }
}
