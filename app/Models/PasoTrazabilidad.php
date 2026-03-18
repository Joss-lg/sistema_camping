<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasoTrazabilidad extends Model
{
    protected $table = 'paso_trazabilidad';

    protected $fillable = [
        'lote_id',
        'etapa',
        'descripcion',
        'fecha',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(ProductoLote::class, 'lote_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
