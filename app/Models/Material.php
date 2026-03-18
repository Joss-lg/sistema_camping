<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $table = 'material';

    protected $fillable = [
        'nombre',
        'categoria_id',
        'unidad_id',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'proveedor_id',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'categoria_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function itemCompras(): HasMany
    {
        return $this->hasMany(ItemCompra::class, 'material_id');
    }

    public function recetas(): HasMany
{
    return $this->hasMany(RecetaMaterial::class, 'material_id');
}
}
