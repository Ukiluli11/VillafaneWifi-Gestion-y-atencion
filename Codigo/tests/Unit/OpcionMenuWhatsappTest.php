<?php

namespace Tests\Unit;

use App\Enums\IntencionConversacion;
use App\Enums\OpcionMenuWhatsapp;
use PHPUnit\Framework\TestCase;

class OpcionMenuWhatsappTest extends TestCase
{
    public function test_convierte_las_opciones_en_intenciones(): void
    {
        $this->assertSame(IntencionConversacion::ConsultaCuenta, OpcionMenuWhatsapp::desdeMensaje('1')->intencion());
        $this->assertSame(IntencionConversacion::ReclamoSoporte, OpcionMenuWhatsapp::desdeMensaje('2. Reclamo')->intencion());
        $this->assertSame(IntencionConversacion::EnvioComprobante, OpcionMenuWhatsapp::desdeMensaje('3')->intencion());
        $this->assertSame(IntencionConversacion::AtencionHumana, OpcionMenuWhatsapp::desdeMensaje('4')->intencion());
        $this->assertSame(IntencionConversacion::DatosPago, OpcionMenuWhatsapp::desdeMensaje('#5')->intencion());
        $this->assertNull(OpcionMenuWhatsapp::desdeMensaje('1.5'));
        $this->assertNull(OpcionMenuWhatsapp::desdeMensaje('-4'));
        $this->assertNull(OpcionMenuWhatsapp::desdeMensaje('sin opción'));
    }
}
