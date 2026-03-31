<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtapaProduccionPlantilla extends Model
{
    use SoftDeletes;

    protected $table = 'etapas_produccion_plantilla';

    protected $fillable = [
        'nombre',
        'descripcion',
        'codigo',
        'tipo_producto_id',
        'numero_secuencia',
        'duracion_estimada_minutos',
        'cantidad_operarios',
        'instrucciones_detalladas',
        'requiere_validacion',
        'es_etapa_critica',
        'activo',
        'tipo_etapa',
    ];

    protected $casts = [
        'requiere_validacion' => 'boolean',
        'es_etapa_critica' => 'boolean',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id');
    }

    public function trazabilidadEtapas(): HasMany
    {
        return $this->hasMany(TrazabilidadEtapa::class, 'etapa_plantilla_id');
    }

    // ============ SCOPES ============

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTipoProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopeTipoEtapa($query, $tipo)
    {
        return $query->where('tipo_etapa', $tipo);
    }

    public function scopeCriticas($query)
    {
        return $query->where('es_etapa_critica', true);
    }

    public function scopePorSecuencia($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId)
                     ->orderBy('numero_secuencia');
    }

    // ============ HELPERS ============

    public function getFlujoProceso(): string
    {
        return "{$this->numero_secuencia}. {$this->nombre}";
    }
}
