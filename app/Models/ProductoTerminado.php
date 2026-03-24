<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoTerminado extends Model
{
    public function pasosTrazabilidad()
    {
        return $this->hasManyThrough(
            PasoTrazabilidad::class,
            ProductoLote::class,
            'producto_id', // Foreign key en ProductoLote
            'lote_id',     // Foreign key en PasoTrazabilidad
            'id',          // Local key en ProductoTerminado
            'id'           // Local key en ProductoLote
        );
    }
    protected $table = 'producto_terminado';

    protected $fillable = [
        'nombre',
        'sku',
        'categoria_id',
        'unidad_id',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'precio_venta',
        'estado_id',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function ordenesProduccion(): HasMany
    {
        return $this->hasMany(OrdenProduccion::class, 'producto_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(ProductoLote::class, 'producto_id');
    }

    public function recetaMateriales(): HasMany
{
    return $this->hasMany(RecetaMaterial::class, 'producto_id');
}

}
