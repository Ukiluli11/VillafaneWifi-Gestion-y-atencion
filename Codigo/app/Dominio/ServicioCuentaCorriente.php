<?php

namespace App\Dominio;

use App\Enums\EstadoCuota;
use App\Models\Cliente;
use App\Models\CuentaReceptora;
use App\Models\Cuota;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;

class ServicioCuentaCorriente
{
    public function instruccionesTransferencia(Cliente $cliente): ?string
    {
        if (! config('services.transferencias.habilitadas', false)) {
            return null;
        }
        $cuenta = CuentaReceptora::query()->whereKey(config('services.transferencias.id_cuenta'))
            ->where('estado', 'activa')->where('tipo', 'mercado_pago')->first();
        if ($cuenta === null) {
            return null;
        }
        $datos = "Transferencia a la cuenta de la empresa (Pruebas académicas).\n"
            ."Mercado Pago — Alias/CVU: {$cuenta->identificador}.\nTitular: {$cuenta->nombre}.\n";
        $cuota = Cuota::query()->whereHas('servicio', fn ($q) => $q->whereBelongsTo($cliente))
            ->whereNull('id_pago')
            ->whereIn('estado', [EstadoCuota::Pendiente->value, EstadoCuota::Vencida->value])
            ->orderBy('fecha_vencimiento')->orderBy('id_cuota')->first();
        if ($cuota === null) {
            return $datos.'Actualmente no tenés cuotas pendientes. No es necesario realizar una transferencia.';
        }
        $monto = number_format((float) $cuota->monto, 2, ',', '.');

        return $datos."Tu cuota pendiente más antigua es #{$cuota->id_cuota}, período {$cuota->periodo}, por \${$monto}. Podés cancelar una o más cuotas completas, comenzando por las más antiguas.\n"
            .'Confirmá que el titular coincida antes de transferir. Si ya pagaste, no repitas la transferencia: adjuntá el comprobante en este chat. '
            .'El personal de cobranza verificará el ingreso en Mercado Pago y aprobará el pago manualmente.';
    }

    /**
     * @return array{total_pendiente: string, total_vencido: string, cantidad_pendientes: int,
     * cantidad_vencidas: int, proximo_vencimiento: ?string, estado: string}
     */
    public function resumir(Cliente $cliente): array
    {
        $cuotas = $this->cuotasDelCliente($cliente);
        $pendientes = $cuotas->whereNull('id_pago');
        $vencidas = $pendientes->where('estado', EstadoCuota::Vencida);
        $totalPendiente = $this->sumar($pendientes);
        $totalVencido = $this->sumar($vencidas);

        return [
            'total_pendiente' => (string) $totalPendiente,
            'total_vencido' => (string) $totalVencido,
            'cantidad_pendientes' => $pendientes->count(),
            'cantidad_vencidas' => $vencidas->count(),
            'proximo_vencimiento' => $pendientes->where('estado', EstadoCuota::Pendiente)
                ->sortBy('fecha_vencimiento')->first()?->fecha_vencimiento?->toDateString(),
            'estado' => $totalVencido->isGreaterThan(BigDecimal::zero())
                ? 'con_deuda'
                : ($totalPendiente->isGreaterThan(BigDecimal::zero()) ? 'al_dia_con_cuotas' : 'al_dia'),
        ];
    }

    /** @return Collection<int, Cuota> */
    public function cuotasDelCliente(Cliente $cliente): Collection
    {
        return Cuota::query()
            ->whereHas('servicio', fn ($consulta) => $consulta->whereBelongsTo($cliente))
            ->with(['servicio.plan', 'pago.cuenta', 'pago.comprobante'])
            ->orderByDesc('periodo')
            ->orderBy('id_servicio')
            ->get();
    }

    /** @param Collection<int, Cuota> $cuotas */
    private function sumar(Collection $cuotas): BigDecimal
    {
        return $cuotas->reduce(
            fn (BigDecimal $total, Cuota $cuota): BigDecimal => $total->plus($cuota->monto),
            BigDecimal::zero(),
        );
    }
}
