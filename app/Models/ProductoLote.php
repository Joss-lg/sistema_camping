<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoLote extends Model
{
    protected $table = 'producto_lote';

    protected $fillable = [
        'producto_id',
        'numero_lote',
        'fecha_produccion',
        'estado_id',
    ];

    protected $casts = [
        'fecha_produccion' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function pasos(): HasMany
    {
        return $this->hasMany(PasoTrazabilidad::class, 'lote_id');
    }
}
