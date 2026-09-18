<?php

namespace App\Models;

use App\Enums\EstadoAvisoVencimiento;
use App\Enums\TipoAvisoVencimiento;
use Database\Factories\AvisoVencimientoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisoVencimiento extends Model
{
    /** @use HasFactory<AvisoVencimientoFactory> */
    use HasFactory;

    protected $table = 'aviso_vencimiento';

    protected $primaryKey = 'id_aviso_vencimiento';

    public $timestamps = false;

    protected $fillable = [
        'id_cuota', 'id_conversacion', 'id_mensaje', 'tipo', 'estado',
        'fecha_hora_ultimo_intento', 'fecha_hora_envio', 'detalle_error',
    ];

    protected $attributes = ['estado' => 'pendiente'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoAvisoVencimiento::class,
            'estado' => EstadoAvisoVencimiento::class,
            'fecha_hora_ultimo_intento' => 'datetime',
            'fecha_hora_envio' => 'datetime',
        ];
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'id_cuota', 'id_cuota');
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'id_mensaje', 'id_mensaje');
    }
}
