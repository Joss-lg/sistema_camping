<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    protected $table = 'orden_compra';

    protected $fillable = [
        'proveedor_id',
        'fecha',
        'fecha_esperada',
        'estado_id',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_esperada' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemCompra::class, 'orden_compra_id');
    }
}