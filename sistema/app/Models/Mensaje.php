<?php

namespace App\Models;

use App\Enums\EstadoEnvioMensaje;
use App\Enums\TipoEmisorMensaje;
use App\Enums\TipoMensaje;
use Database\Factories\MensajeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mensaje extends Model
{
    /** @use HasFactory<MensajeFactory> */
    use HasFactory;

    protected $table = 'mensaje';

    protected $primaryKey = 'id_mensaje';

    public $timestamps = false;

    protected $fillable = [
        'id_conversacion', 'id_usuario', 'id_mensaje_externo', 'fecha_hora', 'tipo',
        'contenido', 'archivo_adjunto', 'tipo_emisor', 'estado_envio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'tipo' => TipoMensaje::class,
            'tipo_emisor' => TipoEmisorMensaje::class,
            'estado_envio' => EstadoEnvioMensaje::class,
        ];
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'id_conversacion', 'id_conversacion');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class, 'id_mensaje', 'id_mensaje');
    }

    public function marcarComoEntregado(): void
    {
        $this->update(['estado_envio' => EstadoEnvioMensaje::Entregado]);
    }

    public function marcarComoLeido(): void
    {
        $this->update(['estado_envio' => EstadoEnvioMensaje::Leido]);
    }

    public function marcarComoFallido(): void
    {
        $this->update(['estado_envio' => EstadoEnvioMensaje::Fallido]);
    }
}
