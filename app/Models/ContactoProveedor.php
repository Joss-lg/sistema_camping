<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoProveedor extends Model
{
    use HasFactory;

    protected $table = 'contactos_proveedores';

    protected $fillable = [
        'proveedor_id',
        'nombre_completo',
        'cargo',
        'departamento',
        'telefono',
        'telefono_movil',
        'email',
        'es_contacto_principal',
    ];

    protected $casts = [
        'es_contacto_principal' => 'boolean',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
