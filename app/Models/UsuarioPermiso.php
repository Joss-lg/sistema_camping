<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioPermiso extends Model
{
    protected $table = 'usuario_permiso';

    protected $fillable = [
        'usuario_id',
        'modulo',
        'puede_ver',
        'puede_editar',
    ];

    protected $casts = [
        'puede_ver' => 'boolean',
        'puede_editar' => 'boolean',
    ];
}
