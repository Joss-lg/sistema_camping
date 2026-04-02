<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $ordenCompra): void {
            if (! empty($ordenCompra->numero_orden)) {
                return;
            }

            $ordenCompra->numero_orden = self::generarNumeroOrden();
        });
    }

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_orden',
        'proveedor_id',
        'user_id',
        'fecha_orden',
        'fecha_entrega_prevista',
        'fecha_entrega_real',
        'estado',
        'total_items',
        'total_cantidad',
        'subtotal',
        'impuestos',
        'descuentos',
        'costo_flete',
        'monto_total',
        'numero_folio_proveedor',
        'numero_contenedor',
        'numero_awb',
        'notas',
        'condiciones_pago',
        'incoterm',
    ];

    protected $casts = [
        'fecha_orden' => 'datetime',
        'fecha_entrega_prevista' => 'date',
        'fecha_entrega_real' => 'date',
        'total_cantidad' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'impuestos' => 'decimal:4',
        'descuentos' => 'decimal:4',
        'costo_flete' => 'decimal:4',
        'monto_total' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Downstream relationships
    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    public function lotesInsumos(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'orden_compra_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'orden_compra_id');
    }

    // ============ SCOPES ============

    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'Confirmada');
    }

    public function scopeRecibidas($query)
    {
        return $query->where('estado', 'Recibida');
    }

    public function scopeProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_orden', [$desde, $hasta]);
    }

    // ============ HELPERS ============

    public function puedeRecibirse(): bool
    {
        return in_array($this->estado, ['Confirmada', 'Pendiente']);
    }

    public function puedeModificarse(): bool
    {
        return in_array($this->estado, ['Pendiente', 'Confirmada']);
    }

    private static function generarNumeroOrden(): string
    {
        do {
            $numero = 'OC-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (self::query()->where('numero_orden', $numero)->exists());

        return $numero;
    }
}
