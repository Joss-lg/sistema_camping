<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaInsumo extends Model
{
    use HasFactory;

    protected $table = 'categorias_insumo';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'categoria_padre_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function categoriaPadre(): BelongsTo
    {
        return $this->belongsTo(CategoriaInsumo::class, 'categoria_padre_id');
    }

    public function subCategorias(): HasMany
    {
        return $this->hasMany(CategoriaInsumo::class, 'categoria_padre_id');
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'categoria_insumo_id');
    }
}
