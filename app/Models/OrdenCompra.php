<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use SoftDeletes;

    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_CONFIRMADA = 'Confirmada';
    public const ESTADO_RECIBIDA = 'Recibida';

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
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', self::ESTADO_CONFIRMADA);
    }

    public function scopeRecibidas($query)
    {
        return $query->where('estado', self::ESTADO_RECIBIDA);
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
        return in_array($this->estado, [self::ESTADO_CONFIRMADA, self::ESTADO_PENDIENTE], true);
    }

    public function puedeModificarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA], true);
    }

    private static function generarNumeroOrden(): string
    {
        $prefijo = 'OC-' . now()->format('Ymd') . '-';

        $ultimoNumeroDelDia = self::query()
            ->where('numero_orden', 'like', $prefijo . '%')
            ->orderByDesc('numero_orden')
            ->value('numero_orden');

        $secuencia = 1;

        if (is_string($ultimoNumeroDelDia) && str_starts_with($ultimoNumeroDelDia, $prefijo)) {
            $sufijo = substr($ultimoNumeroDelDia, strlen($prefijo));

            if (ctype_digit($sufijo)) {
                $secuencia = ((int) $sufijo) + 1;
            }
        }

        do {
            $numero = $prefijo . str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
            $secuencia++;
        } while (self::query()->where('numero_orden', $numero)->exists());

        return $numero;
    }
}
