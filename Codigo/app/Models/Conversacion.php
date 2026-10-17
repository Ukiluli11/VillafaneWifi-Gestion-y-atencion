<?php

namespace App\Models;

use App\Enums\EstadoConversacion;
use App\Enums\IntencionConversacion;
use App\Enums\ModoAtencion;
use Database\Factories\ConversacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        'fecha_hora_cierre', 'estado', 'modo_atencion', 'intencion_actual',
        'paso_actual', 'contexto', 'intentos_interpretacion', 'inicio_atencion', 'fin_atencion',
    ];

    protected $attributes = [
        'estado' => 'abierta',
        'modo_atencion' => 'bot',
        'intentos_interpretacion' => 0,
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_cierre' => 'datetime',
            'estado' => EstadoConversacion::class,
            'modo_atencion' => ModoAtencion::class,
            'intencion_actual' => IntencionConversacion::class,
            'contexto' => 'array',
            'intentos_interpretacion' => 'integer',
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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_conversacion', 'id_conversacion');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'id_conversacion', 'id_conversacion');
    }

    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', '!=', EstadoConversacion::Cerrada->value);
    }

    public function iniciar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Abierta,
            'modo_atencion' => ModoAtencion::Bot,
            'fecha_hora_inicio' => now(),
            'fecha_hora_cierre' => null,
        ]);
    }

    public function cerrar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Cerrada,
            'fecha_hora_cierre' => now(),
            'fin_atencion' => $this->modo_atencion === ModoAtencion::Humano ? now() : $this->fin_atencion,
        ]);
    }

    public function escalar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::Humano,
        ]);
    }

    public function derivarAUsuario(Usuario $usuario): void
    {
        $this->update([
            'id_usuario_atencion' => $usuario->id_usuario,
            'estado' => EstadoConversacion::EnAtencion,
            'modo_atencion' => ModoAtencion::Humano,
            'inicio_atencion' => $this->inicio_atencion ?? now(),
        ]);
    }

    public function agregarMensaje(Mensaje $mensaje): void
    {
        $this->mensajes()->save($mensaje);
    }

    /** @return Collection<int, Mensaje> */
    public function obtenerHistorial(): Collection
    {
        return $this->mensajes()->orderBy('fecha_hora')->orderBy('id_mensaje')->get();
    }

    public function registrarIntentoFallido(): void
    {
        $this->increment('intentos_interpretacion');
        $this->refresh();
    }

    public function reiniciarInterpretacion(): void
    {
        $this->update(['intentos_interpretacion' => 0]);
    }
}
