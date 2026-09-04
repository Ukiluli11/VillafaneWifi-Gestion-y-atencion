<?php

namespace App\Models;

use App\Enums\EstadoCliente;
use App\Enums\EstadoServicio;
use App\Enums\TipoCliente;
use App\Enums\TipoDocumento;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'cliente';

    protected $primaryKey = 'id_cliente';

    public $timestamps = false;

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'nombre_razon_social', 'tipo_cliente',
        'calle_contacto', 'numero_contacto', 'localidad_contacto', 'telefono_whatsapp', 'estado',
    ];

    protected $attributes = [
        'tipo_cliente' => 'particular',
        'estado' => 'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
            'tipo_cliente' => TipoCliente::class,
            'estado' => EstadoCliente::class,
        ];
    }

    protected function numeroDocumento(): Attribute
    {
        return Attribute::make(set: fn (string $valor): string => preg_replace('/\D+/', '', $valor) ?? '');
    }

    protected function telefonoWhatsapp(): Attribute
    {
        return Attribute::make(set: fn (?string $valor): ?string => $valor ? (preg_replace('/\D+/', '', $valor) ?: null) : null);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_cliente', 'id_cliente');
    }

    public function darDeAlta(): void
    {
        $this->update(['estado' => EstadoCliente::Activo]);
    }

    public function suspender(): void
    {
        $this->update(['estado' => EstadoCliente::Suspendido]);
    }

    public function darDeBaja(): void
    {
        $this->update(['estado' => EstadoCliente::Baja]);
        $this->servicios()->where('estado', '!=', EstadoServicio::Baja->value)->update(['estado' => EstadoServicio::Baja->value]);
    }
}
