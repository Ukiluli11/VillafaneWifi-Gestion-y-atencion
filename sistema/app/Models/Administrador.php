<?php

namespace App\Models;

use App\Enums\NivelAcceso;
use Database\Factories\AdministradorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Administrador extends Model
{
    /** @use HasFactory<AdministradorFactory> */
    use HasFactory;

    protected $table = 'administrador';

    protected $primaryKey = 'id_administrador';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_administrador', 'nivel_acceso', 'puede_gestionar_usuarios', 'puede_configurar_planes'];

    protected $attributes = [
        'nivel_acceso' => 'total',
        'puede_gestionar_usuarios' => true,
        'puede_configurar_planes' => true,
    ];

    protected function casts(): array
    {
        return [
            'nivel_acceso' => NivelAcceso::class,
            'puede_gestionar_usuarios' => 'boolean',
            'puede_configurar_planes' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_administrador', 'id_usuario');
    }
}
