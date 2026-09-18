<?php

namespace App\Models;

use App\Enums\EstadoConversacion;
use App\Enums\EstadoFlujoWhatsapp;
use App\Enums\ModoAtencion;
use Database\Factories\ConversacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversacion extends Model
{
    /** @use HasFactory<ConversacionFactory> */
    use HasFactory;

    protected $table = 'conversacion';

    protected $primaryKey = 'id_conversacion';

    public $timestamps = false;

    protected $fillable = [
        'id_cliente', 'id_usuario_atencion', 'numero_whatsapp', 'fecha_hora_inicio',
        'fecha_hora_cierre', 'estado', 'modo_atencion', 'estado_flujo', 'intentos_intencion',
        'datos_registro', 'inicio_atencion', 'fin_atencion',
    ];

    protected $attributes = [
        'estado' => 'abierta',
        'modo_atencion' => 'bot',
        'estado_flujo' => 'menu',
        'intentos_intencion' => 0,
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoConversacion::class,
            'modo_atencion' => ModoAtencion::class,
            'estado_flujo' => EstadoFlujoWhatsapp::class,
            'intentos_intencion' => 'integer',
            'datos_registro' => 'array',
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_cierre' => 'datetime',
            'inicio_atencion' => 'datetime',
            'fin_atencion' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuarioAtencion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_atencion', 'id_usuario');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'id_conversacion', 'id_conversacion');
    }

    /** Tickets generados a partir de esta conversación. */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_conversacion', 'id_conversacion');
    }

    /** Avisos de vencimiento documentados mediante esta conversación. */
    public function avisosVencimiento(): HasMany
    {
        return $this->hasMany(AvisoVencimiento::class, 'id_conversacion', 'id_conversacion');
    }

    public function estaActiva(): bool
    {
        return $this->estado !== EstadoConversacion::Cerrada;
    }
}
