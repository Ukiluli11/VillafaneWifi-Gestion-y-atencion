<?php

namespace App\Models;

use App\Enums\EstadoValidacionComprobante;
use Database\Factories\ComprobanteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Comprobante enviado por un cliente para informar una transferencia. */
class Comprobante extends Model
{
    /** @use HasFactory<ComprobanteFactory> */
    use HasFactory;

    protected $table = 'comprobante';

    protected $primaryKey = 'id_comprobante';

    public $timestamps = false;

    protected $fillable = [
        'id_mensaje', 'id_usuario', 'hash_archivo', 'fecha_recepcion',
        'numero_operacion', 'monto_ocr', 'fecha_ocr', 'confianza_ocr',
        'estado_validacion', 'motivo_rechazo', 'fecha_hora_validacion',
    ];

    protected $attributes = ['estado_validacion' => 'pendiente'];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'datetime',
            'monto_ocr' => 'decimal:2',
            'fecha_ocr' => 'date',
            'confianza_ocr' => 'decimal:2',
            'estado_validacion' => EstadoValidacionComprobante::class,
            'fecha_hora_validacion' => 'datetime',
        ];
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'id_mensaje', 'id_mensaje');
    }

    public function usuarioValidador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class, 'id_comprobante', 'id_comprobante');
    }

    /** Calcula la huella utilizada para detectar archivos repetidos. */
    public static function calcularHash(string $contenidoArchivo): string
    {
        return hash('sha256', $contenidoArchivo);
    }

    /** Indica si la operación o el archivo ya pertenecen a otro registro. */
    public function esDuplicado(): bool
    {
        if ($this->hash_archivo === null && $this->numero_operacion === null) {
            return false;
        }

        return self::query()
            ->when($this->exists, fn ($consulta) => $consulta->where(
                $this->getKeyName(),
                '!=',
                $this->getKey(),
            ))
            ->where(function ($consulta): void {
                $consulta->when($this->hash_archivo, fn ($q) => $q->orWhere('hash_archivo', $this->hash_archivo))
                    ->when($this->numero_operacion, fn ($q) => $q->orWhere('numero_operacion', $this->numero_operacion));
            })
            ->exists();
    }
}
