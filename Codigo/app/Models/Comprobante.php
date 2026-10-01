<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use App\Enums\OrigenComprobante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobante';

    protected $primaryKey = 'id_comprobante';

    protected $fillable = [
        'id_cliente', 'id_pago', 'id_usuario', 'id_conversacion', 'id_mensaje',
        'fecha_recepcion', 'nombre_original', 'mime_type', 'tamanio_bytes',
        'hash_archivo', 'numero_operacion', 'monto_ocr', 'fecha_ocr',
        'ruta_archivo', 'estado_validacion', 'motivo_rechazo', 'fecha_hora_validacion', 'origen',
    ];

    protected $attributes = [
        'estado_validacion' => 'pendiente',
        'origen' => 'panel_manual',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'datetime',
            'tamanio_bytes' => 'integer',
            'monto_ocr' => 'decimal:2',
            'fecha_ocr' => 'date',
            'estado_validacion' => EstadoComprobante::class,
            'fecha_hora_validacion' => 'datetime',
            'origen' => OrigenComprobante::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'id_mensaje', 'id_mensaje');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Pendiente->value);
    }

    public function scopeAprobados(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Aprobado->value);
    }

    public function scopeRechazados(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Rechazado->value);
    }

    public function estaPendiente(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Pendiente;
    }

    public function estaAprobado(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Aprobado;
    }

    public function estaRechazado(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Rechazado;
    }
}
