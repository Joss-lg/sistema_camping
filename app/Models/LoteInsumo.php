<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoteInsumo extends Model
{
    use SoftDeletes;

    protected $table = 'lotes_insumos';

    protected $fillable = [
        'numero_lote',
        'lote_proveedor',
        'insumo_id',
        'orden_compra_id',
        'proveedor_id',
        'fecha_lote',
        'fecha_vencimiento',
        'fecha_recepcion',
        'cantidad_recibida',
        'cantidad_en_stock',
        'cantidad_consumida',
        'cantidad_rechazada',
        'ubicacion_almacen_id',
        'estado_calidad',
        'numero_certificado',
        'observaciones_calidad',
        'numero_contenedor',
        'numero_referencia',
        'user_recepcion_id',
        'notas',
        'numero_certificado_origen',
        'requiere_inspeccion',
        'activo',
    ];

    protected $casts = [
        'cantidad_recibida' => 'decimal:4',
        'cantidad_en_stock' => 'decimal:4',
        'cantidad_consumida' => 'decimal:4',
        'cantidad_rechazada' => 'decimal:4',
        'fecha_lote' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_recepcion' => 'datetime',
        'requiere_inspeccion' => 'boolean',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function ubicacionAlmacen(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_almacen_id');
    }

    public function userRecepcion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_recepcion_id');
    }

    // Downstream relationships
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'lote_insumo_id');
    }

    // Phase 5 relationships
    public function consumosMateriales(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class, 'lote_insumo_id');
    }

    // ============ SCOPES ============

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAceptados($query)
    {
        return $query->where('estado_calidad', 'Aceptado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado_calidad', 'Rechazado');
    }

    public function scopeEnDuda($query)
    {
        return $query->where('estado_calidad', 'Duda');
    }

    public function scopeVencido($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->whereDate('fecha_vencimiento', '<', now());
    }

    public function scopePorVencer($query, $diasAntelacion = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->whereDate('fecha_vencimiento', '<=', now()->addDays($diasAntelacion))
                     ->whereDate('fecha_vencimiento', '>', now());
    }

    public function scopeConStock($query)
    {
        return $query->whereRaw('cantidad_en_stock > 0');
    }

    // ============ HELPERS ============

    /**
     * Verifica si el lote está vencido
     */
    public function estaVencido(): bool
    {
        if (! $this->fecha_vencimiento) {
            return false;
        }
        return now()->isAfter($this->fecha_vencimiento);
    }

    /**
     * Obtiene cantidad disponible (no consumida ni rechazada)
     */
    public function cantidadDisponible(): float
    {
        return $this->cantidad_en_stock - $this->cantidad_consumida;
    }

    /**
     * Verifica si hay stock suficiente
     */
    public function tieneStockSuficiente($cantidad): bool
    {
        return $this->cantidadDisponible() >= $cantidad;
    }

    /**
     * Consume cantidad del lote
     */
    public function consumir($cantidad): bool
    {
        if (! $this->tieneStockSuficiente($cantidad)) {
            return false;
        }
        $this->cantidad_consumida += $cantidad;
        $this->save();
        return true;
    }
}
