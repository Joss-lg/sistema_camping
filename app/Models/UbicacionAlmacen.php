<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbicacionAlmacen extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_almacen';

    protected $fillable = [
        'codigo_ubicacion',
        'nombre',
        'tipo',
        'seccion',
        'estante',
        'nivel',
        'capacidad_maxima',
        'capacidad_actual',
        'activo',
    ];

    protected $casts = [
        'capacidad_maxima' => 'decimal:4',
        'capacidad_actual' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'ubicacion_almacen_id');
    }

    public function lotesInsumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'ubicacion_almacen_id');
    }

    // Phase 4: Movements
    public function movimientosOrigen(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'ubicacion_origen_id');
    }

    public function movimientosDestino(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'ubicacion_destino_id');
    }

    // Phase 5: Products
    public function inventarioProductosTerminados(): HasMany
    {
        return $this->hasMany(InventarioProductoTerminado::class, 'ubicacion_almacen_id');
    }
}
