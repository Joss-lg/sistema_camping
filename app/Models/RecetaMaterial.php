<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaMaterial extends Model
{
    protected $table = 'receta_material';

    protected $fillable = [
        'producto_id',
        'material_id',
        'cantidad_base',
        'merma_porcentaje',
        'activo',
    ];

    protected $casts = [
        'cantidad_base' => 'decimal:4',
        'merma_porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}