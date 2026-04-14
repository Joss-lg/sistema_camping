<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrazabilidadRegistro extends Model
{
    use SoftDeletes;

    protected $table = 'trazabilidad_registros';

    protected $fillable = [
        'trazabilidad_etapa_id',
        'orden_produccion_id',
        'user_id',
        'tipo_evento',
        'estado_anterior',
        'estado_nuevo',
        'descripcion_evento',
        'detalles_cambio',
        'fecha_evento',
        'duracion_actividad_minutos',
        'dispositivo_registro',
        'requiere_seguimiento',
        'notas',
    ];

    protected $casts = [
        'requiere_seguimiento' => 'boolean',
        'fecha_evento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Tipos de evento
    public const EVENTO_INICIO = 'Inicio';
    public const EVENTO_PAUSA = 'Pausa';
    public const EVENTO_REANUDACION = 'Reanudacion';
    public const EVENTO_CAMBIO_ESTADO = 'Cambio Estado';
    public const EVENTO_OBSERVACION = 'Observacion';
    public const EVENTO_RECHAZO = 'Rechazo';
    public const EVENTO_APROBACION = 'Aprobacion';
    public const EVENTO_DEFECTO = 'Defecto Detectado';

    public static $tiposEvento = [
        self::EVENTO_INICIO,
        self::EVENTO_PAUSA,
        self::EVENTO_REANUDACION,
        self::EVENTO_CAMBIO_ESTADO,
        self::EVENTO_OBSERVACION,
        self::EVENTO_RECHAZO,
        self::EVENTO_APROBACION,
        self::EVENTO_DEFECTO,
    ];

    // ============ RELATIONSHIPS ============

    public function trazabilidadEtapa(): BelongsTo
    {
        return $this->belongsTo(TrazabilidadEtapa::class, 'trazabilidad_etapa_id');
    }

    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============ SCOPES ============

    public function scopeTipoEvento($query, $tipo)
    {
        return $query->where('tipo_evento', $tipo);
    }

    public function scopeOrdenProduccion($query, $ordenId)
    {
        return $query->where('orden_produccion_id', $ordenId);
    }

    public function scopeTrazabilidadEtapa($query, $etapaId)
    {
        return $query->where('trazabilidad_etapa_id', $etapaId);
    }

    public function scopeUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_evento', [$desde, $hasta]);
    }

    public function scopeRequiereSeguimiento($query)
    {
        return $query->where('requiere_seguimiento', true);
    }

    public function scopeRequiereSegimiento($query)
    {
        return $this->scopeRequiereSeguimiento($query);
    }

    public function scopeInicios($query)
    {
        return $query->where('tipo_evento', self::EVENTO_INICIO);
    }

    public function scopePausas($query)
    {
        return $query->where('tipo_evento', self::EVENTO_PAUSA);
    }

    public function scopeDefectos($query)
    {
        return $query->where('tipo_evento', self::EVENTO_DEFECTO);
    }

    // ============ HELPERS ============

    public function getDescripcionCompleta(): string
    {
        $base = "[{$this->fecha_evento->format('Y-m-d H:i')}] {$this->tipo_evento}";

        if ($this->estado_anterior && $this->estado_nuevo) {
            $base .= " | {$this->estado_anterior} → {$this->estado_nuevo}";
        }

        return "$base: {$this->descripcion_evento}";
    }

    /**
     * Obtiene el log de toda una orden de producción
     */
    public function getLogOrden($ordenId)
    {
        return static::where('orden_produccion_id', $ordenId)
                    ->orderBy('fecha_evento', 'asc')
                    ->get();
    }
}
