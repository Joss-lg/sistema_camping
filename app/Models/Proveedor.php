<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'codigo_proveedor',
        'razon_social',
        'nombre_comercial',
        'rfc',
        'tipo_proveedor',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono_principal',
        'email_general',
        'sitio_web',
        'dias_credito',
        'tiempo_entrega_dias',
        'limite_credito',
        'descuento_porcentaje',
        'condiciones_pago',
        'calificacion',
        'estatus',
        'certificaciones',
        'notas',
    ];

    protected $casts = [
        'dias_credito' => 'integer',
        'tiempo_entrega_dias' => 'integer',
        'limite_credito' => 'decimal:4',
        'descuento_porcentaje' => 'decimal:2',
        'calificacion' => 'decimal:2',
    ];

    public function contactos(): HasMany
    {
        return $this->hasMany(ContactoProveedor::class, 'proveedor_id');
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'proveedor_id');
    }

    public function lotesInsumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'proveedor_id');
    }
}
