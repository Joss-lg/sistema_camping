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

    public const ESTADO_PRODUCIDO = 'Producido';
    public const ESTADO_APROBADO = 'Aprobado';
    public const ESTADO_RECHAZADO = 'Rechazado';
    public const ESTADO_EMPACADO = 'Empacado';

    public const ESTADO_CALIDAD_PENDIENTE = 'Pendiente Inspección';
    public const ESTADO_CALIDAD_ACEPTADA = 'Aceptada';
    public const ESTADO_CALIDAD_RECHAZADA = 'Rechazada';

    /** @var array<int, string> */
    public const ESTADOS_TRAZABILIDAD = [
        self::ESTADO_PRODUCIDO,
        self::ESTADO_APROBADO,
        self::ESTADO_RECHAZADO,
        self::ESTADO_EMPACADO,
    ];

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
        return $query->where('estado', self::ESTADO_PRODUCIDO);
    }

    public function scopePendienteInspeccion($query)
    {
        return $query->where('estado_calidad', self::ESTADO_CALIDAD_PENDIENTE);
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADO);
    }

    public function scopeEmpacados($query)
    {
        return $query->where('estado', self::ESTADO_EMPACADO);
    }

    public function scopeTipoProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopeCalidadAceptada($query)
    {
        return $query->where('estado_calidad', self::ESTADO_CALIDAD_ACEPTADA);
    }

    public function scopeCalidadRechazada($query)
    {
        return $query->where('estado_calidad', self::ESTADO_CALIDAD_RECHAZADA);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_produccion', [$desde, $hasta]);
    }

    // ============ HELPERS ============

    public function marcarPendienteInspeccion(): void
    {
        $this->estado = self::ESTADO_PRODUCIDO;
        $this->estado_calidad = self::ESTADO_CALIDAD_PENDIENTE;
        $this->save();
    }

    public function marcarAprobado(): void
    {
        $this->estado = self::ESTADO_APROBADO;
        $this->estado_calidad = self::ESTADO_CALIDAD_ACEPTADA;
        $this->fecha_inspeccion = now();
        $this->save();
    }

    public function marcarAprobadoPor(?int $userId): void
    {
        $this->user_inspeccion_id = $userId;
        $this->marcarAprobado();
    }

    public function marcarRechazado($observaciones = ''): void
    {
        $this->estado = self::ESTADO_RECHAZADO;
        $this->estado_calidad = self::ESTADO_CALIDAD_RECHAZADA;
        $this->observaciones_calidad = $observaciones;
        $this->fecha_inspeccion = now();
        $this->save();
    }

    public function marcarRechazadoPor(?int $userId, string $observaciones = ''): void
    {
        $this->user_inspeccion_id = $userId;
        $this->marcarRechazado($observaciones);
    }

    public function marcarEmpacado(): void
    {
        $this->estado = self::ESTADO_EMPACADO;
        $this->fecha_empaque = now();
        $this->save();
    }
}
