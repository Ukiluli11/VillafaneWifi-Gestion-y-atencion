<?php

namespace App\Dominio;

use App\Enums\EstadoCliente;
use App\Enums\TipoCliente;
use App\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza las operaciones de alta, actualización y consulta de clientes.
 */
class ServicioClientes
{
    public function __construct(private readonly ServicioContrataciones $contrataciones) {}

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Cliente
    {
        return DB::transaction(function () use ($datos): Cliente {
            $instalacion = $datos['servicio'] ?? null;
            unset($datos['servicio']);
            $cliente = Cliente::create($datos);
            if ($instalacion !== null) {
                $this->contrataciones->crear($cliente, $instalacion, emitirCuotaInicial: true);
                $cliente->load('servicios.plan');
            }

            return $cliente;
        }, 3);
    }

    /** @param array<string, mixed> $datos */
    public function actualizar(Cliente $cliente, array $datos): Cliente
    {
        return DB::transaction(function () use ($cliente, $datos): Cliente {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($cliente->getKey());
            $cliente->fill($datos);
            if ($cliente->isDirty('telefono_whatsapp')) {
                $cliente->recordatorios_whatsapp = false;
            }
            $cliente->save();

            return $cliente->refresh();
        });
    }

    public function configurarRecordatorios(Cliente $cliente, bool $habilitados): void
    {
        DB::transaction(function () use ($cliente, $habilitados): void {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($cliente->getKey());
            if ($habilitados && (blank($cliente->telefono_whatsapp) || $cliente->estado !== EstadoCliente::Activo)) {
                throw ValidationException::withMessages([
                    'recordatorios_whatsapp' => 'Para habilitar los recordatorios, el cliente debe estar activo y tener un número de WhatsApp registrado.',
                ]);
            }

            $cliente->forceFill(['recordatorios_whatsapp' => $habilitados])->save();
        });
    }

    public function darDeBaja(Cliente $cliente): Cliente
    {
        return DB::transaction(function () use ($cliente): Cliente {
            $cliente->darDeBaja();

            return $cliente->refresh();
        });
    }

    /** @return list<string> */
    public function localidades(): array
    {
        return Cliente::query()->selectRaw('TRIM(localidad_contacto) AS localidad')
            ->whereRaw("TRIM(localidad_contacto) <> ''")
            ->distinct()->orderBy('localidad')->pluck('localidad')->all();
    }

    public function buscar(
        string $termino = '',
        ?EstadoCliente $estado = null,
        ?TipoCliente $tipo = null,
        string $localidad = '',
        ?bool $conWhatsapp = null,
    ): LengthAwarePaginator {
        $termino = trim($termino);
        $numero = preg_replace('/\D+/', '', $termino) ?? '';

        return Cliente::query()
            ->with(['servicios.plan'])
            ->when($estado, fn (Builder $consulta) => $consulta->where('estado', $estado->value))
            ->when($tipo, fn (Builder $consulta) => $consulta->where('tipo_cliente', $tipo->value))
            ->when($localidad !== '', fn (Builder $consulta) => $consulta->whereRaw('TRIM(localidad_contacto) = ?', [$localidad]))
            ->when($conWhatsapp === true, fn (Builder $consulta) => $consulta->whereRaw("TRIM(telefono_whatsapp) <> ''"))
            ->when($conWhatsapp === false, fn (Builder $consulta) => $consulta->whereRaw("COALESCE(TRIM(telefono_whatsapp), '') = ''"))
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
