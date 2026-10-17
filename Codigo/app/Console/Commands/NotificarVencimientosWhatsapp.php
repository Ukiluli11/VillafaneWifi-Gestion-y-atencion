<?php

namespace App\Console\Commands;

use App\Dominio\ServicioNotificacionesVencimiento;
use Illuminate\Console\Command;

class NotificarVencimientosWhatsapp extends Command
{
    protected $signature = 'whatsapp:notificar-vencimientos';

    protected $description = 'Envía por WhatsApp los avisos de cuotas próximas a vencer o vencidas';

    public function handle(ServicioNotificacionesVencimiento $notificaciones): int
    {
        $resultado = $notificaciones->enviar();
        $this->info(sprintf(
            'Enviadas: %d. Omitidas: %d. Fallidas: %d.',
            $resultado['enviadas'],
            $resultado['omitidas'],
            $resultado['fallidas'],
        ));

        return $resultado['fallidas'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
