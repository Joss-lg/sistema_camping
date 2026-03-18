<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EntregaProveedor extends Model
{
    protected $table = 'entrega_proveedor';

    protected $fillable = [
        'usuario_id',
        'proveedor_id',
        'orden_compra_id',
        'material_id',
        'fecha_entrega',
        'cantidad_entregada',
        'estado_calidad',
        'estado_revision',
        'observaciones',
        'observacion_revision',
        'revisado_por_usuario_id',
        'revisado_en',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'revisado_en' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por_usuario_id');
    }
}
