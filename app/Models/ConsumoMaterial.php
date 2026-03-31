<?php

namespace App\Models;

use App\Events\MaterialConsumido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsumoMaterial extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (self $consumo): void {
            event(new MaterialConsumido($consumo));
        });
    }

    protected $table = 'consumos_materiales';

    protected $fillable = [
        'orden_produccion_id',
        'orden_produccion_material_id',
        'insumo_id',
        'lote_insumo_id',
        'unidad_medida_id',
        'cantidad_consumida',
        'cantidad_desperdicio',
        'user_id',
        'fecha_consumo',
        'estado_material',
        'observaciones',
        'requiere_revision',
        'numero_lote_produccion',
        'notas',
    ];

    protected $casts = [
        'cantidad_consumida' => 'decimal:4',
        'cantidad_desperdicio' => 'decimal:4',
        'requiere_revision' => 'boolean',
        'fecha_consumo' => 'datetime',
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

    public function loteInsumo(): BelongsTo
    {
        return $this->belongsTo(LoteInsumo::class, 'lote_insumo_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============ SCOPES ============

    public function scopeConforme($query)
    {
        return $query->where('estado_material', 'Conforme');
    }

    public function scopeNoConforme($query)
    {
        return $query->where('estado_material', 'No Conforme');
    }

    public function scopeRequiereRevision($query)
    {
        return $query->where('requiere_revision', true);
    }

    public function scopeOrdenProduccion($query, $ordenId)
    {
        return $query->where('orden_produccion_id', $ordenId);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_consumo', [$desde, $hasta]);
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ============ HELPERS ============

    public function totalRecusrso(): float
    {
        return $this->cantidad_consumida + $this->cantidad_desperdicio;
    }

    public function porcentajeDesperdicio(): float
    {
        $total = $this->totalRecusrso();
        if ($total == 0) {
            return 0;
        }
        return ($this->cantidad_desperdicio / $total) * 100;
    }
}
