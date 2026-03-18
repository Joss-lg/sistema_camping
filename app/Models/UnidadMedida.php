<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
    protected $table = 'unidad_medida';

    protected $fillable = [
        'nombre',
        'abreviatura',
    ];

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'unidad_id');
    }
}
