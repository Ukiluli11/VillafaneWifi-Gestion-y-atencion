<?php

namespace App\Infraestructura\InteligenciaArtificial;

use App\Contratos\ClasificadorIntencion;
use App\Dominio\ResultadoClasificacionIntencion;
use App\Enums\IntencionWhatsapp;
use App\Enums\OpcionMenuWhatsapp;
use Illuminate\Support\Str;

/** Clasificador de respaldo para desarrollo o indisponibilidad del proveedor. */
class ClasificadorIntencionLocal implements ClasificadorIntencion
{
    public function clasificar(string $mensaje): ResultadoClasificacionIntencion
    {
        $opcion = OpcionMenuWhatsapp::desdeMensaje($mensaje);
        $intencion = match ($opcion) {
            OpcionMenuWhatsapp::ConsultarEstadoCuenta => IntencionWhatsapp::ConsultarEstadoCuenta,
            OpcionMenuWhatsapp::InformarPago => IntencionWhatsapp::InformarPago,
            OpcionMenuWhatsapp::RegistrarReclamo => IntencionWhatsapp::RegistrarReclamo,
            OpcionMenuWhatsapp::SolicitarAtencionHumana => IntencionWhatsapp::SolicitarAtencionHumana,
            null => IntencionWhatsapp::NoReconocida,
        };
        $esOpcionExacta = in_array(Str::of($mensaje)->squish()->toString(), ['1', '2', '3', '4'], true);

        return new ResultadoClasificacionIntencion(
            $intencion,
            $intencion === IntencionWhatsapp::NoReconocida ? 0.0 : ($esOpcionExacta ? 1.0 : 0.80),
            'local',
        );
    }
}
