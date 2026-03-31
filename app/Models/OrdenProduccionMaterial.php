<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenProduccionMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_produccion_materiales';

    protected $fillable = [
        'orden_produccion_id',
        'insumo_id',
        'unidad_medida_id',
        'cantidad_necesaria',
        'cantidad_utilizada',
        'cantidad_desperdicio',
        'estado_asignacion',
        'notas_asignacion',
        'numero_linea',
    ];

    protected $casts = [
        'cantidad_necesaria' => 'decimal:4',
        'cantidad_utilizada' => 'decimal:4',
        'cantidad_desperdicio' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class, 'orden_produccion_material_id');
    }

    // ============ SCOPES ============

    public function scopePendientes($query)
    {
        return $query->where('estado_asignacion', 'Pendiente');
    }

    public function scopeAsignados($query)
    {
        return $query->where('estado_asignacion', 'Asignado');
    }

    public function scopeConsumidos($query)
    {
        return $query->where('estado_asignacion', 'Consumido');
    }

    public function scopeOrdenProduccion($query, $ordenId)
    {
        return $query->where('orden_produccion_id', $ordenId);
    }

    // ============ HELPERS ============

    public function cantidadFaltante(): float
    {
        return max(0, $this->cantidad_necesaria - $this->cantidad_utilizada);
    }

    public function estaCompleto(): bool
    {
        return $this->cantidad_utilizada >= $this->cantidad_necesaria;
    }

    public function porcentajeUtilizado(): float
    {
        if ($this->cantidad_necesaria == 0) {
            return 0;
        }
        return ($this->cantidad_utilizada / $this->cantidad_necesaria) * 100;
    }
}
