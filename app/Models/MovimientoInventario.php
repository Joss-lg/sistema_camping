<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoInventario extends Model
{
    use SoftDeletes;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'tipo_movimiento',
        'insumo_id',
        'lote_insumo_id',
        'orden_compra_id',
        'orden_produccion_id',
        'cantidad',
        'unidad_medida_id',
        'ubicacion_origen_id',
        'ubicacion_destino_id',
        'referencia_documento',
        'motivo',
        'user_id',
        'fecha_movimiento',
        'notas',
        'numero_lote_produccion',
        'saldo_anterior',
        'saldo_posterior',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'saldo_anterior' => 'decimal:4',
        'saldo_posterior' => 'decimal:4',
        'fecha_movimiento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ TIPO MOVIMIENTO CONSTANTS ============
    public const TIPO_ENTRADA = 'Entrada';
    public const TIPO_SALIDA = 'Salida';
    public const TIPO_AJUSTE = 'Ajuste';
    public const TIPO_CONSUMO = 'Consumo';
    public const TIPO_TRASPASO = 'Traspaso';
    public const TIPO_DEVOLUCION = 'Devolución';

    public static $tiposMovimiento = [
        self::TIPO_ENTRADA,
        self::TIPO_SALIDA,
        self::TIPO_AJUSTE,
        self::TIPO_CONSUMO,
        self::TIPO_TRASPASO,
        self::TIPO_DEVOLUCION,
    ];

    // ============ RELATIONSHIPS ============

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function loteInsumo(): BelongsTo
    {
        return $this->belongsTo(LoteInsumo::class, 'lote_insumo_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function evaluacionesCalidad(): HasMany
    {
        return $this->hasMany(CalidadMaterialEvaluacion::class, 'movimiento_inventario_id');
    }

    // ============ SCOPES ============

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_ENTRADA);
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_SALIDA);
    }

    public function scopeAjustes($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_AJUSTE);
    }

    public function scopeConsumo($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_CONSUMO);
    }

    public function scopeTraspaso($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_TRASPASO);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_movimiento', [$desde, $hasta]);
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorInsumo($query, $insumoId)
    {
        return $query->where('insumo_id', $insumoId);
    }

    public function scopePorLote($query, $loteId)
    {
        return $query->where('lote_insumo_id', $loteId);
    }

    public function scopePorOrdenCompra($query, $ordenId)
    {
        return $query->where('orden_compra_id', $ordenId);
    }

    // ============ HELPERS ============

    /**
     * Verifica si es movimiento de entrada (aumenta stock)
     */
    public function esEntrada(): bool
    {
        return in_array($this->tipo_movimiento, [self::TIPO_ENTRADA, self::TIPO_DEVOLUCION, self::TIPO_AJUSTE]);
    }

    /**
     * Verifica si es movimiento de salida (disminuye stock)
     */
    public function esSalida(): bool
    {
        return in_array($this->tipo_movimiento, [self::TIPO_SALIDA, self::TIPO_CONSUMO]);
    }

    /**
     * Obtiene descripción legible del movimiento
     */
    public function getDescripcion(): string
    {
        $base = "{$this->tipo_movimiento}: {$this->cantidad} {$this->unidadMedida->abreviatura} de {$this->insumo->nombre}";

        if ($this->tipo_movimiento === self::TIPO_TRASPASO && $this->ubicacionOrigen && $this->ubicacionDestino) {
            return "$base | De: {$this->ubicacionOrigen->codigo_ubicacion} → A: {$this->ubicacionDestino->codigo_ubicacion}";
        }

        return $base;
    }
}
