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

/**
 * Modelo Conversacion
 * Administra el ciclo de vida de la sesión de chat por WhatsApp (bot o atención humana).
 */
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

    /**
     * Cliente asociado a la conversación (si fue identificado).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Usuario/operador que atiende la conversación en modo humano.
     */
    public function usuarioAtencion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_atencion', 'id_usuario');
    }

    /**
     * Mensajes enviados y recibidos durante la conversación.
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'id_conversacion', 'id_conversacion');
    }

    /**
     * Tickets de soporte derivados de esta conversación.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_conversacion', 'id_conversacion');
    }

    /**
     * Comprobantes adjuntados en esta conversación.
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'id_conversacion', 'id_conversacion');
    }

    /**
     * Filtra las conversaciones que no se encuentran cerradas.
     */
    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', '!=', EstadoConversacion::Cerrada->value);
    }

    /**
     * Caso de Uso: Abrir o reutilizar conversación (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + iniciar() : void
     */
    public function iniciar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Abierta,
            'modo_atencion' => ModoAtencion::Bot,
            'fecha_hora_inicio' => now(),
            'fecha_hora_cierre' => null,
        ]);
    }

    /**
     * Caso de Uso: Cerrar conversación resuelta por el bot / Cerrar atención escalada (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + cerrar() : void
     */
    public function cerrar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Cerrada,
            'fecha_hora_cierre' => now(),
            'fin_atencion' => $this->modo_atencion === ModoAtencion::Humano ? now() : $this->fin_atencion,
        ]);
    }

    /**
     * Caso de Uso: Derivar tras segundo fallo de IA / Escalar atención (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + escalar() : void
     */
    public function escalar(): void
    {
        $this->update([
            'estado' => EstadoConversacion::Escalada,
            'modo_atencion' => ModoAtencion::Humano,
        ]);
    }

    /**
     * Caso de Uso: Tomar control de conversación escalada (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + derivarAUsuario(usuario: Usuario) : void
     */
    public function derivarAUsuario(Usuario $usuario): void
    {
        $this->update([
            'id_usuario_atencion' => $usuario->id_usuario,
            'estado' => EstadoConversacion::EnAtencion,
            'modo_atencion' => ModoAtencion::Humano,
            'inicio_atencion' => $this->inicio_atencion ?? now(),
        ]);
    }

    /**
     * Caso de Uso: Registrar mensaje en el historial (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + agregarMensaje(mensaje: Mensaje) : void
     */
    public function agregarMensaje(Mensaje $mensaje): void
    {
        $this->mensajes()->save($mensaje);
    }

    /**
     * Caso de Uso: Consultar historial de conversación (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + obtenerHistorial() : List<Mensaje>
     *
     * @return Collection<int, Mensaje>
     */
    public function obtenerHistorial(): Collection
    {
        return $this->mensajes()->orderBy('fecha_hora')->orderBy('id_mensaje')->get();
    }

    /**
     * Caso de Uso: Solicitar reformulación del mensaje (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + registrarIntentoFallido() : void
     */
    public function registrarIntentoFallido(): void
    {
        $this->increment('intentos_interpretacion');
        $this->refresh();
    }

    /**
     * Caso de Uso: Reiniciar interpretación al cambiar de paso (Módulo 2)
     * Clase UML: Conversacion
     * Método UML: + reiniciarInterpretacion() : void
     */
    public function reiniciarInterpretacion(): void
    {
        $this->update(['intentos_interpretacion' => 0]);
    }
}
