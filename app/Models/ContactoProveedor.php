<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoProveedor extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $contacto): void {
            if (! $contacto->es_contacto_principal || ! $contacto->proveedor_id) {
                return;
            }

            static::query()
                ->where('proveedor_id', $contacto->proveedor_id)
                ->when($contacto->exists, fn ($query) => $query->where('id', '!=', $contacto->id))
                ->where('es_contacto_principal', true)
                ->update(['es_contacto_principal' => false]);
        });
    }

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
