<?php

namespace App\Dominio;

use App\Enums\EstadoCuota;
use App\Models\Cliente;
use App\Models\Cuota;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;

class ServicioCuentaCorriente
{
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
            ->with(['servicio.plan', 'pago.cuenta'])
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
