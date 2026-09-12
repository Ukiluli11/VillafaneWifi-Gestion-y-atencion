<?php

namespace App\Dominio;

use App\Enums\EstadoConversacion;
use App\Models\Conversacion;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reúne las consultas utilizadas por el panel de conversaciones.
 */
class ServicioConsultaConversaciones
{
    /**
     * Busca conversaciones por cliente, teléfono o estado y devuelve las más
     * recientes primero.
     *
     * @return LengthAwarePaginator<int, Conversacion>
     */
    public function buscar(?string $texto, ?string $estado): LengthAwarePaginator
    {
        $texto = trim((string) $texto);
        $estadosValidos = array_column(EstadoConversacion::cases(), 'value');

        return Conversacion::query()
            ->with(['cliente', 'usuarioAtencion'])
            ->withCount('mensajes')
            ->when($texto !== '', function ($consulta) use ($texto): void {
                $consulta->where(function ($filtro) use ($texto): void {
                    $filtro->where('numero_whatsapp', 'like', "%{$texto}%")
                        ->orWhereHas('cliente', fn ($clientes) => $clientes
                            ->where('nombre_razon_social', 'like', "%{$texto}%"));
                });
            })
            ->when(in_array($estado, $estadosValidos, true), fn ($consulta) => $consulta->where('estado', $estado))
            ->orderByDesc('fecha_hora_inicio')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Carga toda la información necesaria para mostrar el historial.
     */
    public function cargarDetalle(Conversacion $conversacion): Conversacion
    {
        return $conversacion->load([
            'cliente',
            'usuarioAtencion',
            'mensajes' => fn ($consulta) => $consulta
                ->with('usuario')
                ->orderBy('fecha_hora')
                ->orderBy('id_mensaje'),
        ]);
    }
}
