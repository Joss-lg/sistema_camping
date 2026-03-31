<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReporteGenerado extends Model
{
    use SoftDeletes;

    protected $table = 'reportes_generados';

    protected $fillable = [
        'codigo_reporte',
        'nombre_reporte',
        'tipo_reporte',
        'formato',
        'parametros',
        'ruta_archivo',
        'generado_por_user_id',
        'fecha_desde',
        'fecha_hasta',
        'total_registros',
        'tamano_bytes',
        'estado',
        'expiracion_at',
        'notas',
    ];

    protected $casts = [
        'parametros' => 'array',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'expiracion_at' => 'datetime',
        'total_registros' => 'integer',
        'tamano_bytes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_user_id');
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_reporte', $tipo);
    }

    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function marcarDescargado(): void
    {
        $this->estado = 'Descargado';
        $this->save();
    }
}
