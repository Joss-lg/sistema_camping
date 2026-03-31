<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoProducto extends Model
{
    use HasFactory;

    protected $table = 'tipos_producto';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'color',
        'activo',
        'stock_minimo_terminado',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'stock_minimo_terminado' => 'decimal:4',
    ];

    public function etapasProduccionPlantilla(): HasMany
    {
        return $this->hasMany(EtapaProduccionPlantilla::class, 'tipo_producto_id');
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'tipo_producto_id');
    }

    public function ordenesProduccion(): HasMany
    {
        return $this->hasMany(OrdenProduccion::class, 'tipo_producto_id');
    }

    public function productosTerminados(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class, 'tipo_producto_id');
    }

    public function inventarioProductosTerminados(): HasMany
    {
        return $this->hasMany(InventarioProductoTerminado::class, 'tipo_producto_id');
    }
}
