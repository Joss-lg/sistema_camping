<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompraDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_compra_detalles';

    protected $fillable = [
        'orden_compra_id',
        'numero_linea',
        'insumo_id',
        'unidad_medida_id',
        'cantidad_solicitada',
        'cantidad_recibida',
        'cantidad_aceptada',
        'precio_unitario',
        'descuento_porcentaje',
        'subtotal',
        'lote_esperado',
        'fecha_entrega_esperada_linea',
        'estado_linea',
        'notas',
        'notas_recepcion',
    ];

    protected $casts = [
        'cantidad_solicitada' => 'decimal:4',
        'cantidad_recibida' => 'decimal:4',
        'cantidad_aceptada' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'descuento_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:4',
        'fecha_entrega_esperada_linea' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    // ============ SCOPES ============

    public function scopePendientes($query)
    {
        return $query->where('estado_linea', 'Pendiente');
    }

    public function scopeRecibidas($query)
    {
        return $query->where('estado_linea', 'Recibida');
    }

    public function scopeAceptadas($query)
    {
        return $query->where('estado_linea', 'Aceptada');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado_linea', 'Rechazada');
    }

    // ============ HELPERS ============

    public function cantidadFaltante(): float
    {
        return max(0, $this->cantidad_solicitada - $this->cantidad_aceptada);
    }

    public function porcentajeRecibido(): float
    {
        if ($this->cantidad_solicitada == 0) {
            return 0;
        }
        return ($this->cantidad_recibida / $this->cantidad_solicitada) * 100;
    }

    public function porcentajeAceptado(): float
    {
        if ($this->cantidad_solicitada == 0) {
            return 0;
        }
        return ($this->cantidad_aceptada / $this->cantidad_solicitada) * 100;
    }
}
