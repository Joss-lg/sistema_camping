<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductoTerminado extends Model
{
    use SoftDeletes;

    protected $table = 'productos_terminados';

    protected $fillable = [
        'numero_lote_produccion',
        'numero_serie',
        'orden_produccion_id',
        'tipo_producto_id',
        'user_responsable_id',
        'fecha_produccion',
        'fecha_finalizacion',
        'fecha_empaque',
        'cantidad_producida',
        'unidad_medida_id',
        'estado',
        'estado_calidad',
        'observaciones_calidad',
        'user_inspeccion_id',
        'fecha_inspeccion',
        'costo_produccion',
        'notas',
        'codigo_barras',
        'codigo_qr',
        'imagen_url',
    ];

    protected $casts = [
        'cantidad_producida' => 'decimal:4',
        'costo_produccion' => 'decimal:4',
        'fecha_produccion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'fecha_empaque' => 'datetime',
        'fecha_inspeccion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id');
    }

    public function userResponsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_responsable_id');
    }

    public function userInspeccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_inspeccion_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function inventario(): HasMany
    {
        return $this->hasMany(InventarioProductoTerminado::class, 'producto_terminado_id');
    }

    public function etapasTrazabilidad(): HasManyThrough
    {
        return $this->hasManyThrough(
            TrazabilidadEtapa::class,
            OrdenProduccion::class,
            'id',
            'orden_produccion_id',
            'orden_produccion_id',
            'id'
        );
    }

    // ============ SCOPES ============

    public function scopeProducido($query)
    {
        return $query->where('estado', 'Producido');
    }

    public function scopePendienteInspeccion($query)
    {
        return $query->where('estado', 'Control Calidad Pendiente');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'Aprobado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'Rechazado');
    }

    public function scopeEmpacados($query)
    {
        return $query->where('estado', 'Empacado');
    }

    public function scopeTipoProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopeCalidadAceptada($query)
    {
        return $query->where('estado_calidad', 'Aceptada');
    }

    public function scopeCalidadRechazada($query)
    {
        return $query->where('estado_calidad', 'Rechazada');
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_produccion', [$desde, $hasta]);
    }

    // ============ HELPERS ============

    public function marcarPendienteInspeccion(): void
    {
        $this->estado = 'Control Calidad Pendiente';
        $this->save();
    }

    public function marcarAprobado(): void
    {
        $this->estado = 'Aprobado';
        $this->estado_calidad = 'Aceptada';
        $this->fecha_inspeccion = now();
        $this->save();
    }

    public function marcarRechazado($observaciones = ''): void
    {
        $this->estado = 'Rechazado';
        $this->estado_calidad = 'Rechazada';
        $this->observaciones_calidad = $observaciones;
        $this->fecha_inspeccion = now();
        $this->save();
    }

    public function marcarEmpacado(): void
    {
        $this->estado = 'Empacado';
        $this->fecha_empaque = now();
        $this->save();
    }
}
