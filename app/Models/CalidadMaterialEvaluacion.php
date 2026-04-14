<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalidadMaterialEvaluacion extends Model
{
    use SoftDeletes;

    /** @var array<int, string> */
    public const CRITERIOS_ESTANDAR = [
        'documentacion_completa',
        'integridad_empaque',
        'especificaciones_tecnicas',
        'condicion_fisica',
        'fecha_vigencia_valida',
    ];

    protected $table = 'calidad_material_evaluaciones';

    protected $fillable = [
        'movimiento_inventario_id',
        'lote_insumo_id',
        'insumo_id',
        'user_id',
        'resultado',
        'criterios',
        'cumplimiento_porcentaje',
        'observaciones',
        'fecha_evaluacion',
    ];

    protected $casts = [
        'criterios' => 'array',
        'cumplimiento_porcentaje' => 'decimal:2',
        'fecha_evaluacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const RESULTADO_APROBADO = 'APROBADO';
    public const RESULTADO_OBSERVADO = 'OBSERVADO';
    public const RESULTADO_RECHAZADO = 'RECHAZADO';

    public function movimientoInventario(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_inventario_id');
    }

    public function loteInsumo(): BelongsTo
    {
        return $this->belongsTo(LoteInsumo::class, 'lote_insumo_id');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
