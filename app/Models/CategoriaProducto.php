<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    protected $table = 'categoria_producto';

    protected $fillable = [
        'nombre',
    ];

    public function productosTerminados(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class, 'categoria_id');
    }
}
