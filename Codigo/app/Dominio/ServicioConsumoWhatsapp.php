<?php

namespace App\Dominio;

use App\Exceptions\LimiteConsumoWhatsappException;
use App\Models\Mensaje;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ServicioConsumoWhatsapp
{
    public function reservar(bool $esPlantilla, string $destinatario, ?string $nombrePlantilla = null): void
    {
        if (! config('services.whatsapp.control_consumo_habilitado', true)) {
            return;
        }
        if ($esPlantilla) {
            $permitidos = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.plantillas_destinatarios'))));
            $normalizar = static function (string $numero): string {
                $numero = preg_replace('/\D/', '', $numero) ?? '';

                return str_starts_with($numero, '549') ? '54'.substr($numero, 3) : $numero;
            };
            $nombres = array_filter([
                config('services.whatsapp.plantilla_vencimiento'), config('services.whatsapp.plantilla_vencida'),
                config('services.comprobantes.plantilla_aprobado'), config('services.comprobantes.plantilla_rechazado'),
            ]);
            if (! config('services.whatsapp.plantillas_reales_habilitadas', false)
                || ! in_array($normalizar($destinatario), array_map($normalizar, $permitidos), true)
                || ! in_array($nombrePlantilla, $nombres, true)) {
                throw new LimiteConsumoWhatsappException('Envío de plantilla bloqueado: solo se permiten pruebas habilitadas a los teléfonos configurados. Las pruebas simuladas no requieren un envío real.');
            }
        }

        DB::transaction(function () use ($esPlantilla): void {
            $clave = $this->clave();
            DB::table('consumo_whatsapp_mes')->insertOrIgnore([
                ...$clave, 'servicios_reservados' => $this->serviciosHistoricos(),
                'plantillas_reservadas' => 0, 'dia_plantillas' => null, 'plantillas_del_dia' => 0,
            ]);
            $fila = DB::table('consumo_whatsapp_mes')->where($clave)->lockForUpdate()->first();
            if (! $esPlantilla) {
                if ($fila->servicios_reservados >= max(0, (int) config('services.whatsapp.limite_servicios_mes', 900))) {
                    throw new LimiteConsumoWhatsappException('Se alcanzó el límite mensual de respuestas de la demostración. El envío real quedó bloqueado para controlar el consumo.');
                }
                DB::table('consumo_whatsapp_mes')->where($clave)->increment('servicios_reservados');

                return;
            }
            $hoy = CarbonImmutable::now('UTC')->toDateString();
            $delDia = $fila->dia_plantillas === $hoy ? $fila->plantillas_del_dia : 0;
            if ($fila->plantillas_reservadas >= max(0, (int) config('services.whatsapp.limite_plantillas_mes', 4))
                || $delDia >= max(0, (int) config('services.whatsapp.limite_plantillas_dia', 2))) {
                throw new LimiteConsumoWhatsappException('Se alcanzó el límite de plantillas de prueba del día o del mes. No se realizó otro envío cobrable.');
            }
            DB::table('consumo_whatsapp_mes')->where($clave)->update([
                'plantillas_reservadas' => $fila->plantillas_reservadas + 1,
                'dia_plantillas' => $hoy, 'plantillas_del_dia' => $delDia + 1,
            ]);
        });
    }

    /** @return array<string, mixed> */
    public function resumir(): array
    {
        $clave = $this->clave();
        $fila = DB::table('consumo_whatsapp_mes')->where($clave)->first();

        return [
            'periodo' => $clave['periodo'],
            'servicios' => $fila?->servicios_reservados ?? $this->serviciosHistoricos(),
            'plantillas' => $fila?->plantillas_reservadas ?? 0,
            'plantillas_hoy' => $fila?->dia_plantillas === CarbonImmutable::now('UTC')->toDateString() ? $fila->plantillas_del_dia : 0,
            'limite_servicios' => (int) config('services.whatsapp.limite_servicios_mes', 900),
            'limite_plantillas' => (int) config('services.whatsapp.limite_plantillas_mes', 4),
            'limite_diario' => (int) config('services.whatsapp.limite_plantillas_dia', 2),
            'control_habilitado' => (bool) config('services.whatsapp.control_consumo_habilitado', true),
            'plantillas_habilitadas' => (bool) config('services.whatsapp.plantillas_reales_habilitadas', false),
            'simulacion' => (bool) config('services.whatsapp.modo_simulacion'),
        ];
    }

    /** @return array{id_telefono: string, periodo: string} */
    private function clave(): array
    {
        return ['id_telefono' => (string) config('services.whatsapp.phone_number_id'), 'periodo' => CarbonImmutable::now('UTC')->format('Y-m')];
    }

    private function serviciosHistoricos(): int
    {
        $inicio = CarbonImmutable::now('UTC')->startOfMonth();

        return Mensaje::query()->where('tipo_emisor', '!=', 'cliente')
            ->whereNotNull('id_mensaje_externo')->where('id_mensaje_externo', 'not like', 'wamid.simulado.%')
            ->where('fecha_hora', '>=', $inicio)->where('fecha_hora', '<', $inicio->addMonth())->count();
    }
}
