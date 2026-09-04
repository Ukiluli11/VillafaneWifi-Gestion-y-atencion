<?php

namespace App\Dominio;

use App\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ServicioClientes
{
    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Cliente
    {
        return DB::transaction(fn (): Cliente => Cliente::create($datos));
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Cliente $cliente, array $datos): Cliente
    {
        return DB::transaction(function () use ($cliente, $datos): Cliente {
            $cliente->update($datos);

            return $cliente->refresh();
        });
    }

    public function darDeBaja(Cliente $cliente): Cliente
    {
        return DB::transaction(function () use ($cliente): Cliente {
            $cliente->darDeBaja();

            return $cliente->refresh();
        });
    }

    public function buscar(string $termino = ''): LengthAwarePaginator
    {
        $termino = trim($termino);
        $numero = preg_replace('/\D+/', '', $termino) ?? '';

        return Cliente::query()
            ->with(['servicios.plan'])
            ->when($termino !== '', function (Builder $consulta) use ($termino, $numero): void {
                $consulta->where(function (Builder $filtro) use ($termino, $numero): void {
                    $filtro->where('nombre_razon_social', 'like', "%{$termino}%")
                        ->orWhere('localidad_contacto', 'like', "%{$termino}%");
                    if ($numero !== '') {
                        $filtro->orWhere('numero_documento', 'like', "%{$numero}%")
                            ->orWhere('telefono_whatsapp', 'like', "%{$numero}%");
                    }
                });
            })
            ->orderBy('nombre_razon_social')
            ->orderBy('id_cliente')
            ->paginate(20)
            ->withQueryString();
    }
}
