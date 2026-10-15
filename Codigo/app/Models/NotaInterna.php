<?php

namespace App\Models;

use Database\Factories\NotaInternaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modelo NotaInterna
 * Representa una anotación técnica o administrativa interna vinculada a un ticket.
 */
class NotaInterna extends Model
{
    /** @use HasFactory<NotaInternaFactory> */
    use HasFactory;

    protected $table = 'nota_interna';

    protected $primaryKey = 'id_nota';

    public $timestamps = false;

    protected $fillable = [
        'id_ticket',
        'id_usuario',
        'fecha_hora',
        'contenido',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    /**
     * Ticket de soporte al que pertenece la nota.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'id_ticket', 'id_ticket');
    }

    /**
     * Usuario/empleado autor de la nota interna.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Caso de Uso: Registrar nota interna en ticket (Módulo 2)
     * Clase UML: NotaInterna
     * Método UML: + registrar() : void
     *
     * Guarda la nota interna asignándole fecha y hora actual si no fue provista.
     */
    public function registrar(): void
    {
        if (empty($this->fecha_hora)) {
            $this->fecha_hora = Carbon::now();
        }
        $this->save();
    }
}
