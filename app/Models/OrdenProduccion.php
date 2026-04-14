<?php

namespace App\Models;

use App\Events\OrdenProduccionCompletada;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrdenProduccion extends Model
{
    use SoftDeletes;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_EN_PROCESO = 'En Proceso';
    public const ESTADO_EN_PAUSA = 'En Pausa';
    public const ESTADO_FINALIZADA = 'Finalizada';
    public const ESTADO_COMPLETADA_LEGACY = 'Completada';
    public const ESTADO_CANCELADA = 'Cancelada';

    /**
     * Opciones mostradas en filtros de UI.
     * Se evita exponer el estado legacy para mantener una sola etiqueta visible.
     *
     * @var array<int, string>
     */
    public const ESTADOS_FILTRO_UI = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_EN_PAUSA,
        self::ESTADO_FINALIZADA,
        self::ESTADO_CANCELADA,
    ];

    /**
     * Estados persistidos que representan cierre de orden.
     *
     * @var array<int, string>
     */
    public const ESTADOS_FINALIZADAS = [
        self::ESTADO_FINALIZADA,
        self::ESTADO_COMPLETADA_LEGACY,
    ];

    protected $table = 'ordenes_produccion';

    protected $fillable = [
        'numero_orden',
        'tipo_producto_id',
        'user_id',
        'fecha_orden',
        'fecha_inicio_prevista',
        'fecha_fin_prevista',
        'fecha_inicio_real',
        'fecha_fin_real',
        'cantidad_produccion',
        'unidad_medida_id',
        'estado',
        'etapa_fabricacion_actual',
        'maquina_asignada',
        'turno_asignado',
        'etapas_totales',
        'etapas_completadas',
        'porcentaje_completado',
        'costo_estimado',
        'costo_real',
        'notas',
        'es_plantilla_bom',
        'prioridad',
        'requiere_calidad',
        'especificaciones_especiales',
    ];

    protected $casts = [
        'cantidad_produccion' => 'decimal:4',
        'costo_estimado' => 'decimal:4',
        'costo_real' => 'decimal:4',
        'porcentaje_completado' => 'decimal:2',
        'requiere_calidad' => 'boolean',
        'es_plantilla_bom' => 'boolean',
        'fecha_orden' => 'datetime',
        'fecha_inicio_prevista' => 'date',
        'fecha_fin_prevista' => 'date',
        'fecha_inicio_real' => 'date',
        'fecha_fin_real' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $orden): void {
            if (blank($orden->numero_orden)) {
                $orden->numero_orden = self::generarCodigoOrden();
            }
        });

        static::updated(function (self $orden): void {
            if (! $orden->wasChanged('estado')) {
                return;
            }

            $estadoAnterior = (string) $orden->getOriginal('estado');
            $estadoActual = (string) $orden->estado;

            if (self::esEstadoFinalizado($estadoAnterior) && ! self::esEstadoFinalizado($estadoActual)) {
                self::ocultarTerminadosPorReapertura($orden->id);
            }
        });
    }

    // ============ RELATIONSHIPS ============

    /**
     * Tipo de producto de la orden.
     */
    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id');
    }

    /**
     * Usuario responsable/creador de la orden.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Unidad de medida de la cantidad planificada.
     */
    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    /**
     * Materiales planificados para la orden.
     */
    public function materiales(): HasMany
    {
        return $this->hasMany(OrdenProduccionMaterial::class, 'orden_produccion_id');
    }

    /**
     * Consumos reales de materiales de la orden.
     */
    public function consumosMateriales(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class, 'orden_produccion_id');
    }

    /**
     * Etapas de trazabilidad de ejecución de la orden.
     */
    public function trazabilidadEtapas(): HasMany
    {
        return $this->hasMany(TrazabilidadEtapa::class, 'orden_produccion_id');
    }

    /**
     * Alias de compatibilidad para código legado.
     */
    public function etapasTrazabilidad(): HasMany
    {
        return $this->trazabilidadEtapas();
    }

    /**
     * Eventos granulares de trazabilidad de la orden.
     */
    public function trazabilidadRegistros(): HasMany
    {
        return $this->hasMany(TrazabilidadRegistro::class, 'orden_produccion_id');
    }

    /**
     * Productos terminados asociados a la orden.
     */
    public function productosTerminados(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class, 'orden_produccion_id');
    }

    // ============ SCOPES ============

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeEstado($query, string $estado)
    {
        return $this->scopePorEstado($query, $estado);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', self::ESTADO_EN_PROCESO);
    }

    public function scopeCompletadas($query)
    {
        return $query->whereIn('estado', self::ESTADOS_FINALIZADAS);
    }

    public function scopePorProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad)->orderBy('fecha_orden');
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_orden', [$desde, $hasta]);
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO, self::ESTADO_EN_PAUSA]);
    }

    public static function esEstadoFinalizado(?string $estado): bool
    {
        return in_array((string) $estado, self::ESTADOS_FINALIZADAS, true);
    }

    public static function normalizarEstadoVisual(?string $estado): string
    {
        if (self::esEstadoFinalizado($estado)) {
            return self::ESTADO_FINALIZADA;
        }

        return (string) ($estado ?: self::ESTADO_PENDIENTE);
    }

    // ============ HELPERS ============

    public static function generarCodigoOrden(): string
    {
        $year = now()->year;
        $prefix = "OP-{$year}-";

        $ultimo = static::query()
            ->where('numero_orden', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if (! $ultimo) {
            return $prefix . '001';
        }

        $correlativo = (int) Str::afterLast($ultimo->numero_orden, '-');

        return $prefix . str_pad((string) ($correlativo + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Genera un número de lote sugerido para productos terminados de esta orden.
     * Nota: la orden no persiste lote propio en su tabla; se usa en producto terminado.
     */
    public function generarNumeroLote(): string
    {
        return sprintf('LOTE-%s-%03d', now()->format('Ymd'), $this->id ?: 0);
    }

    public function generarEtapasTrazabilidad(): void
    {
        $plantillas = EtapaProduccionPlantilla::query()
            ->where('tipo_producto_id', $this->tipo_producto_id)
            ->where('activo', true)
            ->orderBy('numero_secuencia')
            ->get();

        if ($plantillas->isEmpty()) {
            return;
        }

        $yaExisten = $this->trazabilidadEtapas()->exists();
        if ($yaExisten) {
            return;
        }

        foreach ($plantillas as $plantilla) {
            TrazabilidadEtapa::create([
                'orden_produccion_id' => $this->id,
                'etapa_plantilla_id' => $plantilla->id,
                'numero_secuencia' => $plantilla->numero_secuencia,
                'numero_ejecucion' => 1,
                'fecha_inicio_prevista' => now(),
                'fecha_fin_prevista' => now()->addMinutes((int) $plantilla->duracion_estimada_minutos),
                'duracion_estimada_minutos' => $plantilla->duracion_estimada_minutos,
                'cantidad_operarios' => $plantilla->cantidad_operarios,
                'estado' => self::ESTADO_PENDIENTE,
            ]);
        }

        $this->forceFill([
            'etapas_totales' => $plantillas->count(),
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ])->save();
    }

    public function puedeIniciar(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE && $this->etapas_totales > 0;
    }

    public function marcarEnProceso(): void
    {
        $this->estado = self::ESTADO_EN_PROCESO;
        if (!$this->fecha_inicio_real) {
            $this->fecha_inicio_real = now()->toDateString();
        }
        $this->save();
    }

    public function marcarCompletada(): void
    {
        if (self::esEstadoFinalizado((string) $this->estado) && (int) $this->etapas_completadas === (int) $this->etapas_totales) {
            return;
        }

        $this->estado = self::ESTADO_FINALIZADA;
        $this->etapas_completadas = $this->etapas_totales;
        $this->porcentaje_completado = 100;
        $this->fecha_fin_real = now()->toDateString();
        $this->save();

        event(new OrdenProduccionCompletada($this));
    }

    public function calcularProgreso(): void
    {
        if ($this->etapas_totales === 0) {
            $this->porcentaje_completado = 0;
        } else {
            $this->porcentaje_completado = ($this->etapas_completadas / $this->etapas_totales) * 100;
        }
        $this->save();
    }

    private static function ocultarTerminadosPorReapertura(int $ordenId): void
    {
        $productoIds = ProductoTerminado::query()
            ->where('orden_produccion_id', $ordenId)
            ->pluck('id');

        if ($productoIds->isEmpty()) {
            return;
        }

        InventarioProductoTerminado::query()
            ->whereIn('producto_terminado_id', $productoIds)
            ->delete();

        ProductoTerminado::query()
            ->whereIn('id', $productoIds)
            ->delete();
    }
}
