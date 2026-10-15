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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Modelo Cliente
 * Representa al titular del servicio, sus datos de contacto y su estado.
 */
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

    /**
     * Limpia el documento dejando únicamente caracteres numéricos.
     */
    protected function numeroDocumento(): Attribute
    {
        return Attribute::make(set: fn (string $valor): string => preg_replace('/\D+/', '', $valor) ?? '');
    }

    /**
     * Normaliza el teléfono de WhatsApp eliminando caracteres no numéricos.
     */
    protected function telefonoWhatsapp(): Attribute
    {
        return Attribute::make(set: fn (?string $valor): ?string => $valor ? (preg_replace('/\D+/', '', $valor) ?: null) : null);
    }

    /**
     * Servicios de internet contratados por este cliente.
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Conversaciones de WhatsApp registradas para este cliente.
     */
    public function conversaciones(): HasMany
    {
        return $this->hasMany(Conversacion::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Comprobantes de pago enviados por este cliente.
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Cuotas emitidas a través de los servicios pertenecientes a este cliente.
     */
    public function cuotas(): HasManyThrough
    {
        return $this->hasManyThrough(Cuota::class, Servicio::class, 'id_cliente', 'id_servicio');
    }

    /**
     * Caso de Uso: Registrar cliente y servicio (Módulo 1)
     * Clase UML: Cliente
     * Método UML: + darDeAlta() : void
     */
    public function darDeAlta(): void
    {
        $this->update(['estado' => EstadoCliente::Activo]);
    }

    /**
     * Caso de Uso: Suspender cliente por mora (Módulo 1)
     * Clase UML: Cliente
     * Método UML: + suspender() : void
     */
    public function suspender(): void
    {
        $this->update(['estado' => EstadoCliente::Suspendido]);
    }

    /**
     * Caso de Uso: Dar de baja definitiva del servicio (Módulo 1)
     * Clase UML: Cliente
     * Método UML: + darDeBaja() : void
     */
    public function darDeBaja(): void
    {
        $this->update(['estado' => EstadoCliente::Baja]);
        $this->servicios()->where('estado', '!=', EstadoServicio::Baja->value)->update(['estado' => EstadoServicio::Baja->value]);
    }
}
