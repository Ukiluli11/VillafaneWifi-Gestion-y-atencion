<?php

namespace App\Dominio;

use App\Contratos\ClasificadorIntencion;
use App\Enums\IntencionWhatsapp;
use App\Infraestructura\InteligenciaArtificial\ClasificadorIntencionLocal;
use Illuminate\Support\Str;

/** Decide cuándo consultar IA y aplica el umbral de confianza del sistema. */
class ServicioReconocimientoIntencionWhatsapp
{
    public function __construct(
        private readonly ClasificadorIntencion $clasificador,
        private readonly ClasificadorIntencionLocal $clasificadorLocal,
    ) {}

    public function reconocer(?string $mensaje): ResultadoClasificacionIntencion
    {
        $texto = Str::of((string) $mensaje)->squish()->toString();
        if (in_array($texto, ['1', '2', '3', '4'], true)) {
            return $this->clasificadorLocal->clasificar($texto);
        }

        $resultado = $this->clasificador->clasificar($texto);
        $umbral = (float) config('services.gemini.umbral_confianza', 0.65);
        if ($resultado->intencion === IntencionWhatsapp::NoReconocida
            || $resultado->confianza < $umbral) {
            return new ResultadoClasificacionIntencion(
                IntencionWhatsapp::NoReconocida,
                $resultado->confianza,
                $resultado->origen,
            );
        }

        return $resultado;
    }
}
