<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfiguracionSistema extends Model
{
    use SoftDeletes;

    protected $table = 'configuraciones_sistema';

    protected $fillable = [
        'clave',
        'valor',
        'tipo_dato',
        'categoria',
        'descripcion',
        'es_publica',
        'editable',
        'orden_visualizacion',
        'activo',
    ];

    protected $casts = [
        'es_publica' => 'boolean',
        'editable' => 'boolean',
        'activo' => 'boolean',
        'orden_visualizacion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePublicas($query)
    {
        return $query->where('es_publica', true)->where('activo', true);
    }

    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function getValorTipadoAttribute()
    {
        if ($this->valor === null) {
            return null;
        }

        return match ($this->tipo_dato) {
            'integer' => (int) $this->valor,
            'decimal' => (float) $this->valor,
            'boolean' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->valor, true),
            default => $this->valor,
        };
    }
}
