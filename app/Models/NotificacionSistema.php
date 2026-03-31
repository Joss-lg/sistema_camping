<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificacionSistema extends Model
{
    use SoftDeletes;

    protected $table = 'notificaciones_sistema';

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'modulo',
        'prioridad',
        'user_id',
        'role_id',
        'estado',
        'fecha_programada',
        'fecha_leida',
        'enviada_at',
        'requiere_accion',
        'url_accion',
        'metadata',
        'notas',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requiere_accion' => 'boolean',
        'fecha_programada' => 'datetime',
        'fecha_leida' => 'datetime',
        'enviada_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeLeidas($query)
    {
        return $query->where('estado', 'Leida');
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeModulo($query, string $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function marcarLeida(): void
    {
        $this->estado = 'Leida';
        $this->fecha_leida = now();
        $this->save();
    }
}
