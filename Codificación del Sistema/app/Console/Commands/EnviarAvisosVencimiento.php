<?php

namespace App\Console\Commands;

use App\Dominio\ServicioAvisosVencimiento;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('avisos:enviar-vencimientos')]
#[Description('Envía por WhatsApp los avisos de cuotas próximas a vencer o vencidas')]
class EnviarAvisosVencimiento extends Command
{
    /** Ejecuta el control diario y muestra un resumen auditable. */
    public function handle(ServicioAvisosVencimiento $servicio): int
    {
        $resultado = $servicio->procesar();
        $this->table(
            ['Candidatos', 'Enviados', 'Omitidos', 'Fallidos'],
            [[$resultado['candidatos'], $resultado['enviados'], $resultado['omitidos'], $resultado['fallidos']]],
        );

        return $resultado['fallidos'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
