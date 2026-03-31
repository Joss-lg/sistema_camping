<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insumo extends Model
{
    use SoftDeletes;

    protected $table = 'insumos';

    protected $fillable = [
        'codigo_insumo',
        'nombre',
        'descripcion',
        'especificaciones_tecnicas',
        'categoria_insumo_id',
        'unidad_medida_id',
        'tipo_producto_id',
        'stock_minimo',
        'stock_actual',
        'stock_reservado',
        'proveedor_id',
        'codigo_proveedor_insumo',
        'precio_unitario',
        'precio_costo',
        'ubicacion_almacen_id',
        'estado',
        'activo',
        'unidad_compra',
        'cantidad_minima_orden',
        'imagen_url',
    ];

    protected $casts = [
        'especificaciones_tecnicas' => 'array',
        'stock_minimo' => 'decimal:4',
        'stock_actual' => 'decimal:4',
        'stock_reservado' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'precio_costo' => 'decimal:4',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    /**
     * Categoría a la que pertenece el insumo.
     */
    public function categoriaInsumo(): BelongsTo
    {
        return $this->belongsTo(CategoriaInsumo::class, 'categoria_insumo_id');
    }

    /**
     * Unidad de medida del inventario principal.
     */
    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    /**
     * Tipo de producto al que está orientado el insumo.
     */
    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id');
    }

    /**
     * Proveedor principal del insumo.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Ubicación de almacén por defecto.
     */
    public function ubicacionAlmacen(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_almacen_id');
    }

    /**
     * Lotes registrados del insumo.
     */
    public function lotesInsumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'insumo_id');
    }

    /**
     * Detalles de órdenes de compra donde participa el insumo.
     */
    public function ordenesCompraDetalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'insumo_id');
    }

    /**
     * Movimientos de inventario del insumo.
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'insumo_id');
    }

    /**
     * Material planificado en órdenes de producción.
     */
    public function ordenesProduccionMateriales(): HasMany
    {
        return $this->hasMany(OrdenProduccionMaterial::class, 'insumo_id');
    }

    /**
     * Consumos reales del insumo en producción.
     */
    public function consumosMateriales(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class, 'insumo_id');
    }

    // ============ SCOPES ============

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereRaw('stock_actual > 0');
    }

    public function scopeBajoStock($query)
    {
        return $query->whereRaw('stock_actual <= stock_minimo');
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeCategoria($query, $categoriaId)
    {
        return $query->where('categoria_insumo_id', $categoriaId);
    }

    public function scopeProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function necesitaReabastecimiento(): bool
    {
        return (float) $this->stock_actual <= (float) $this->stock_minimo;
    }
}
