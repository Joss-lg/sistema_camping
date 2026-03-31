<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioProductoTerminado extends Model
{
    use SoftDeletes;

    protected $table = 'inventario_productos_terminados';

    protected $fillable = [
        'producto_terminado_id',
        'tipo_producto_id',
        'ubicacion_almacen_id',
        'cantidad_en_almacen',
        'unidad_medida_id',
        'cantidad_reservada',
        'fecha_ingreso_almacen',
        'fecha_vencimiento',
        'estado',
        'precio_unitario',
        'valor_total_inventario',
        'notas',
        'requiere_inspeccion_periodica',
    ];

    protected $casts = [
        'cantidad_en_almacen' => 'decimal:4',
        'cantidad_reservada' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'valor_total_inventario' => 'decimal:4',
        'requiere_inspeccion_periodica' => 'boolean',
        'fecha_ingreso_almacen' => 'date',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function productoTerminado(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_terminado_id');
    }

    public function tipoProducto(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id');
    }

    public function ubicacionAlmacen(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_almacen_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    // ============ SCOPES ============

    public function scopeEnAlmacen($query)
    {
        return $query->where('estado', 'En Almacén');
    }

    public function scopeReservados($query)
    {
        return $query->where('estado', 'Reservado');
    }

    public function scopeEnviados($query)
    {
        return $query->where('estado', 'Enviado');
    }

    public function scopeVendidos($query)
    {
        return $query->where('estado', 'Vendido');
    }

    public function scopeTipoProducto($query, $tipoProductoId)
    {
        return $query->where('tipo_producto_id', $tipoProductoId);
    }

    public function scopeUbicacion($query, $ubicacionId)
    {
        return $query->where('ubicacion_almacen_id', $ubicacionId);
    }

    public function scopeVencido($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->whereDate('fecha_vencimiento', '<', now());
    }

    public function scopePorVencer($query, $diasAntelacion = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                     ->whereDate('fecha_vencimiento', '<=', now()->addDays($diasAntelacion))
                     ->whereDate('fecha_vencimiento', '>', now());
    }

    // ============ HELPERS ============

    public function cantidadDisponible(): float
    {
        return max(0, $this->cantidad_en_almacen - $this->cantidad_reservada);
    }

    public function estaVencido(): bool
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }
        return now()->isAfter($this->fecha_vencimiento);
    }

    public function actualizarValor(): void
    {
        $this->valor_total_inventario = $this->cantidad_en_almacen * $this->precio_unitario;
        $this->save();
    }

    public function reservar($cantidad): bool
    {
        if ($this->cantidadDisponible() >= $cantidad) {
            $this->cantidad_reservada += $cantidad;
            $this->save();
            return true;
        }
        return false;
    }

    public function liberarReserva($cantidad): void
    {
        $this->cantidad_reservada = max(0, $this->cantidad_reservada - $cantidad);
        $this->save();
    }

    public function confirmarVenta($cantidad): bool
    {
        if ($this->cantidad_reservada >= $cantidad) {
            $this->cantidad_en_almacen -= $cantidad;
            $this->cantidad_reservada -= $cantidad;
            if ($this->cantidad_en_almacen === 0) {
                $this->estado = 'Vendido';
            }
            $this->save();
            return true;
        }
        return false;
    }
}
