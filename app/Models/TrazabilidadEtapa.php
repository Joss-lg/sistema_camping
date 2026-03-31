<?php

namespace App\Models;

use App\Events\EtapaCompletada;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TrazabilidadEtapa extends Model
{
    use SoftDeletes;

    protected $table = 'trazabilidad_etapas';

    protected $fillable = [
        'orden_produccion_id',
        'etapa_plantilla_id',
        'responsable_id',
        'numero_secuencia',
        'numero_ejecucion',
        'fecha_inicio_prevista',
        'fecha_fin_prevista',
        'fecha_inicio_real',
        'fecha_fin_real',
        'fecha_aprobacion',
        'aprobado_por',
        'duracion_real_minutos',
        'duracion_estimada_minutos',
        'variacion_porcentaje',
        'estado',
        'cantidad_operarios',
        'operarios_asignados',
        'observaciones_etapa',
        'resultado_validacion',
        'notas_validacion',
        'notas_produccion',
    ];

    protected $casts = [
        'duracion_real_minutos' => 'integer',
        'duracion_estimada_minutos' => 'integer',
        'variacion_porcentaje' => 'decimal:2',
        'fecha_inicio_prevista' => 'datetime',
        'fecha_fin_prevista' => 'datetime',
        'fecha_inicio_real' => 'datetime',
        'fecha_fin_real' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    /**
     * Orden de producción asociada.
     */
    public function ordenProduccion(): BelongsTo
    {
        return $this->belongsTo(OrdenProduccion::class, 'orden_produccion_id');
    }

    /**
     * Plantilla base de la etapa.
     */
    public function etapaPlantilla(): BelongsTo
    {
        return $this->belongsTo(EtapaProduccionPlantilla::class, 'etapa_plantilla_id');
    }

    /**
     * Responsable del area encargado de la etapa.
     */
    public function responsableArea(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Usuario que aprobo manualmente la etapa.
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    /**
     * Eventos de trazabilidad registrados para la etapa.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(TrazabilidadRegistro::class, 'trazabilidad_etapa_id');
    }

    // ============ SCOPES ============

    public function scopeOrdenProduccion($query, $ordenId)
    {
        return $query->where('orden_produccion_id', $ordenId);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', 'En Proceso');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'Finalizada');
    }

    public function scopeEsperandoAprobacion($query)
    {
        return $query->where('estado', 'Esperando Aprobacion')
            ->orWhere('estado', 'Esperando Aprobación');
    }

    public function scopeConDefecto($query)
    {
        return $query->where('estado', 'Con Defecto');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'Rechazada');
    }

    public function scopePorSecuencia($query, $ordenId)
    {
        return $query->where('orden_produccion_id', $ordenId)
                     ->orderBy('numero_secuencia');
    }

    // ============ HELPERS ============

    public function iniciar(): void
    {
        $this->estado = 'En Proceso';
        $this->fecha_inicio_real = now();
        $this->save();

        $this->registros()->create([
            'orden_produccion_id' => $this->orden_produccion_id,
            'user_id' => Auth::id() ?? $this->ordenProduccion?->user_id,
            'tipo_evento' => TrazabilidadRegistro::EVENTO_INICIO,
            'estado_anterior' => 'Pendiente',
            'estado_nuevo' => 'En Proceso',
            'descripcion_evento' => 'Inicio de etapa ' . ($this->etapaPlantilla->nombre ?? ('#' . $this->etapa_plantilla_id)),
            'fecha_evento' => now(),
        ]);
    }

    public function completar(): void
    {
        $estadoAnterior = $this->estado;
        $this->estado = 'Finalizada';
        $this->fecha_fin_real = now();

        // Calcular duración real
        if ($this->fecha_inicio_real) {
            $this->duracion_real_minutos = $this->fecha_inicio_real->diffInMinutes($this->fecha_fin_real);

            // Calcular variación porcentual
            if ($this->duracion_estimada_minutos > 0) {
                $diff = $this->duracion_real_minutos - $this->duracion_estimada_minutos;
                $this->variacion_porcentaje = ($diff / $this->duracion_estimada_minutos) * 100;
            }
        }

        $this->save();

        $this->registros()->create([
            'orden_produccion_id' => $this->orden_produccion_id,
            'user_id' => Auth::id() ?? $this->ordenProduccion?->user_id,
            'tipo_evento' => TrazabilidadRegistro::EVENTO_APROBACION,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Finalizada',
            'descripcion_evento' => 'Fin de etapa ' . ($this->etapaPlantilla->nombre ?? ('#' . $this->etapa_plantilla_id)),
            'fecha_evento' => now(),
        ]);

        event(new EtapaCompletada($this));
    }

    public function marcarIniciada(): void
    {
        $this->iniciar();
    }

    public function marcarCompletada(): void
    {
        $this->completar();
    }

    public function estaAtrasada(): bool
    {
        return now()->isAfter($this->fecha_fin_prevista) && $this->estado !== 'Finalizada';
    }

    public function tiempoRestante(): int
    {
        if ($this->estado === 'Finalizada' || !$this->fecha_fin_prevista) {
            return 0;
        }
        return max(0, now()->diffInMinutes($this->fecha_fin_prevista));
    }

    public function getTrazaCompleta()
    {
        return $this->registros()->orderBy('fecha_evento')->get();
    }
}
