<?php

namespace App\Console\Commands;

use App\Dominio\ServicioNotificacionesVencimiento;
use Illuminate\Console\Command;

class NotificarVencimientosWhatsapp extends Command
{
    protected $signature = 'whatsapp:notificar-vencimientos {--id-cuota= : Procesar solo una cuota}';

    protected $description = 'Envía por WhatsApp los avisos de cuotas próximas a vencer o vencidas';

    public function handle(ServicioNotificacionesVencimiento $notificaciones): int
    {
        $opcion = $this->option('id-cuota');
        if ($opcion !== null && (! ctype_digit((string) $opcion) || (int) $opcion < 1)) {
            $this->error('El ID de cuota debe ser un entero positivo.');

            return self::INVALID;
        }

        $resultado = $notificaciones->enviar($opcion === null ? null : (int) $opcion);
        $this->info(sprintf(
            'Enviadas: %d. Omitidas: %d. Fallidas: %d.',
            $resultado['enviadas'],
            $resultado['omitidas'],
            $resultado['fallidas'],
        ));

        return $resultado['fallidas'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
