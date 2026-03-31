<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
    use HasFactory;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'tipo',
        'factor_conversion_base',
        'activo',
    ];

    protected $casts = [
        'factor_conversion_base' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'unidad_medida_id');
    }

    public function ordenesCompraDetalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'unidad_medida_id');
    }

    // Phase 5 relationships (to be added when those models exist)
    public function ordenesProduccion(): HasMany
    {
        return $this->hasMany('App\\Models\\OrdenProduccion', 'unidad_medida_id');
    }

    public function ordenesProduccionMateriales(): HasMany
    {
        return $this->hasMany('App\\Models\\OrdenProduccionMaterial', 'unidad_medida_id');
    }

    public function consumosMateriales(): HasMany
    {
        return $this->hasMany('App\\Models\\ConsumoMaterial', 'unidad_medida_id');
    }

    // Phase 4 relationships
    public function lotesInsumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'unidad_medida_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'unidad_medida_id');
    }

    // Phase 5 relationship
    public function productosTerminados(): HasMany
    {
        return $this->hasMany('App\\Models\\ProductoTerminado', 'unidad_medida_id');
    }
}
