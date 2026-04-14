<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use Illuminate\Support\Collection;

class AbastecimientoOrdenCompraPersister
{
    private const NOTE_OC_STOCK_BAJO = 'Generada automaticamente por stock bajo.';
    private const NOTE_DETALLE_SUGERIDO = 'Sugerida por abastecimiento automatico.';
    private const NUMERO_ORDEN_SIMULACION_FORMAT = 'SIM-%s-%d';

    /**
     * @param Collection<int, array{insumo: Insumo, cantidad_solicitada: float, precio_unitario: float, subtotal: float}> $lineas
     * @return array{numero_orden: string, detalles_creados: int, total_cantidad: float, subtotal: float, orden_creada: bool}
     */
    public function persistir(
        bool $dryRun,
        int $proveedorId,
        int $responsableId,
        string $fechaEntrega,
        string $condicionesPago,
        Collection $lineas
    ): array {
        $numeroOrden = sprintf(self::NUMERO_ORDEN_SIMULACION_FORMAT, now()->format('YmdHis'), $proveedorId);
        $detallesCreados = 0;
        $ordenCreada = false;
        $totalCantidad = round((float) $lineas->sum('cantidad_solicitada'), 4);
        $subtotal = round((float) $lineas->sum('subtotal'), 4);

        if ($dryRun) {
            return [
                'numero_orden' => $numeroOrden,
                'detalles_creados' => $detallesCreados,
                'total_cantidad' => $totalCantidad,
                'subtotal' => $subtotal,
                'orden_creada' => $ordenCreada,
            ];
        }

        $ordenCompra = OrdenCompra::query()->create([
            'proveedor_id' => $proveedorId,
            'user_id' => $responsableId,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => $fechaEntrega,
            'estado' => OrdenCompra::ESTADO_PENDIENTE,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
            'notas' => self::NOTE_OC_STOCK_BAJO,
            'condiciones_pago' => $condicionesPago,
            'incoterm' => null,
        ]);

        $numeroOrden = (string) $ordenCompra->numero_orden;

        foreach ($lineas->values() as $index => $linea) {
            $insumo = $linea['insumo'];

            OrdenCompraDetalle::query()->create([
                'orden_compra_id' => $ordenCompra->id,
                'numero_linea' => $index + 1,
                'insumo_id' => (int) $insumo->id,
                'unidad_medida_id' => (int) $insumo->unidad_medida_id,
                'cantidad_solicitada' => (float) $linea['cantidad_solicitada'],
                'precio_unitario' => (float) $linea['precio_unitario'],
                'descuento_porcentaje' => 0,
                'subtotal' => (float) $linea['subtotal'],
                'estado_linea' => OrdenCompraDetalle::ESTADO_PENDIENTE,
                'notas' => self::NOTE_DETALLE_SUGERIDO,
            ]);

            $detallesCreados++;
        }

        $ordenCompra->update([
            'total_items' => $detallesCreados,
            'total_cantidad' => $totalCantidad,
            'subtotal' => $subtotal,
            'monto_total' => $subtotal,
        ]);

        return [
            'numero_orden' => $numeroOrden,
            'detalles_creados' => $detallesCreados,
            'total_cantidad' => $totalCantidad,
            'subtotal' => $subtotal,
            'orden_creada' => true,
        ];
    }
}
