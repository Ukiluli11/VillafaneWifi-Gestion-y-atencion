<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use App\Enums\OrigenComprobante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modelo Comprobante
 * Gestiona el comprobante de pago enviado por el cliente, su OCR y validación.
 */
class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobante';

    protected $primaryKey = 'id_comprobante';

    protected $fillable = [
        'id_cliente', 'id_pago', 'id_usuario', 'id_conversacion', 'id_mensaje',
        'fecha_recepcion', 'nombre_original', 'mime_type', 'tamanio_bytes',
        'hash_archivo', 'numero_operacion', 'monto_ocr', 'fecha_ocr',
        'ruta_archivo', 'disco_archivo', 'estado_ocr', 'error_ocr', 'completitud_ocr',
        'estado_validacion', 'motivo_rechazo', 'fecha_hora_validacion', 'origen',
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
            'completitud_ocr' => 'decimal:2',
            'fecha_ocr' => 'date',
            'estado_validacion' => EstadoComprobante::class,
            'fecha_hora_validacion' => 'datetime',
            'origen' => OrigenComprobante::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Cliente titular que envía el comprobante.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Pago registrado tras aprobar este comprobante.
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }

    /**
     * Usuario que validó o rechazó el comprobante.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Conversación de WhatsApp donde fue recibido el comprobante.
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    /**
     * Mensaje que contenía el archivo adjunto.
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'id_mensaje', 'id_mensaje');
    }

    /**
     * Filtra comprobantes en estado pendiente.
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Pendiente->value);
    }

    /**
     * Filtra comprobantes en estado aprobado.
     */
    public function scopeAprobados(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Aprobado->value);
    }

    /**
     * Filtra comprobantes en estado rechazado.
     */
    public function scopeRechazados(Builder $query): Builder
    {
        return $query->where('estado_validacion', EstadoComprobante::Rechazado->value);
    }

    /**
     * Comprueba si el comprobante está pendiente de revisión.
     */
    public function estaPendiente(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Pendiente;
    }

    /**
     * Comprueba si el comprobante ya fue aprobado.
     */
    public function estaAprobado(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Aprobado;
    }

    /**
     * Comprueba si el comprobante fue rechazado.
     */
    public function estaRechazado(): bool
    {
        return $this->estado_validacion === EstadoComprobante::Rechazado;
    }

    /**
     * Caso de Uso: Aprobar comprobante y registrar pago (Módulo 3)
     * Clase UML: Comprobante
     * Método UML: + aprobar(usuario: Usuario) : void
     * Servicio coordinador: ServicioComprobantes::conciliarYAprobar()
     */
    public function aprobar(Usuario $usuario): void
    {
        $this->update([
            'estado_validacion' => EstadoComprobante::Aprobado,
            'id_usuario' => $usuario->id_usuario,
            'fecha_hora_validacion' => Carbon::now(),
        ]);
    }

    /**
     * Caso de Uso: Rechazar comprobante de pago (Módulo 3)
     * Clase UML: Comprobante
     * Método UML: + rechazar(usuario: Usuario, motivo: String) : void
     * Servicio coordinador: ServicioComprobantes::rechazarComprobante()
     */
    public function rechazar(Usuario $usuario, string $motivo): void
    {
        $this->update([
            'estado_validacion' => EstadoComprobante::Rechazado,
            'motivo_rechazo' => $motivo,
            'id_usuario' => $usuario->id_usuario,
            'fecha_hora_validacion' => Carbon::now(),
        ]);
    }

    /**
     * Caso de Uso: Verificar duplicidad de comprobante (Módulo 3)
     * Clase UML: Comprobante
     * Método UML: + esDuplicado() : boolean
     * Verifica mediante hash SHA-256 si ya existe otro comprobante aprobado con el mismo archivo.
     */
    public function esDuplicado(): bool
    {
        if (empty($this->hash_archivo)) {
            return false;
        }

        return static::where('hash_archivo', $this->hash_archivo)
            ->where('id_comprobante', '!=', $this->id_comprobante)
            ->where('estado_validacion', EstadoComprobante::Aprobado->value)
            ->exists();
    }
}
