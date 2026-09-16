<?php

namespace App\Dominio;

use App\Enums\EstadoValidacionComprobante;
use App\Models\Comprobante;
use Illuminate\Pagination\LengthAwarePaginator;

/** Reúne las consultas de la bandeja de comprobantes recibidos. */
class ServicioConsultaComprobantes
{
    /**
     * Filtra comprobantes por cliente, operación o estado.
     *
     * @return LengthAwarePaginator<int, Comprobante>
     */
    public function buscar(?string $texto, ?string $estado): LengthAwarePaginator
    {
        $texto = trim((string) $texto);
        $estadosValidos = array_column(EstadoValidacionComprobante::cases(), 'value');

        return Comprobante::query()
            ->with([
                'mensaje.conversacion.cliente',
                'usuarioValidador',
            ])
            ->when($texto !== '', function ($consulta) use ($texto): void {
                $consulta->where(function ($filtro) use ($texto): void {
                    $filtro->where('numero_operacion', 'like', "%{$texto}%")
                        ->orWhereHas('mensaje.conversacion.cliente', fn ($clientes) => $clientes
                            ->where('nombre_razon_social', 'like', "%{$texto}%"));
                });
            })
            ->when(in_array($estado, $estadosValidos, true), fn ($consulta) => $consulta
                ->where('estado_validacion', $estado))
            ->orderByRaw("CASE WHEN estado_validacion = 'pendiente' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_recepcion')
            ->paginate(15)
            ->withQueryString();
    }
}
