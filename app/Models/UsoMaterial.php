<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsoMaterial extends Model
{
    protected $table = 'uso_material';

protected $fillable = [
    'orden_produccion_id',
    'material_id',
    'cantidad_necesaria',
    'cantidad_usada',
    'cantidad_merma',
    'motivo_merma',
];

protected $casts = [
    'cantidad_necesaria' => 'decimal:2',
    'cantidad_usada' => 'decimal:2',
    'cantidad_merma' => 'decimal:2',
];

    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
