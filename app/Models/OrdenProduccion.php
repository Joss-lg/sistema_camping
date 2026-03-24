<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenProduccion extends Model
{
    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }
    protected $table = 'orden_produccion';

    protected $fillable = [
        'producto_id',
        'cantidad',
        'cantidad_completada',
        'cantidad_ingresada',
        'fecha_inicio',
        'fecha_esperada',
        'estado_id',
        'usuario_id',
        'responsable_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'cantidad_completada' => 'decimal:2',
        'cantidad_ingresada' => 'decimal:2',
        'fecha_inicio' => 'datetime',
        'fecha_esperada' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function usosMaterial(): HasMany
    {
        return $this->hasMany(UsoMaterial::class, 'orden_produccion_id');
    }
}
