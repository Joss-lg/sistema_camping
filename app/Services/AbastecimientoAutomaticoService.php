<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\User;
use Illuminate\Support\Collection;

class AbastecimientoAutomaticoService
{
    /**
     * @return array<string, mixed>
     */
    public function generarOrdenes(bool $dryRun = false): array
    {
        $insumosBajoStock = Insumo::query()
            ->with(['proveedor:id,razon_social,nombre_comercial,estatus,dias_credito,tiempo_entrega_dias,condiciones_pago'])
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->whereNotNull('proveedor_id')
            ->orderBy('proveedor_id')
            ->orderBy('id')
            ->get();

        $resultado = [
            'dry_run' => $dryRun,
            'insumos_detectados' => $insumosBajoStock->count(),
            'ordenes_creadas' => 0,
            'detalles_creados' => 0,
            'proveedores_omitidos' => 0,
            'insumos_omitidos_por_orden_abierta' => 0,
            'ordenes' => [],
        ];

        if ($insumosBajoStock->isEmpty()) {
            return $resultado;
        }

        $responsableId = $this->resolverResponsableId();

        if (! $responsableId) {
            $resultado['error'] = 'No existe un usuario activo para asignar ordenes automaticas.';

            return $resultado;
        }

        $insumosPorProveedor = $insumosBajoStock->groupBy('proveedor_id');

        foreach ($insumosPorProveedor as $proveedorId => $insumosProveedor) {
            $proveedor = $insumosProveedor->first()?->proveedor;

            if (! $proveedor || mb_strtolower((string) $proveedor->estatus) !== 'activo') {
                $resultado['proveedores_omitidos']++;
                continue;
            }

            $insumosDisponibles = $this->filtrarInsumosSinOrdenAbierta($insumosProveedor, (int) $proveedorId, $resultado);

            if ($insumosDisponibles->isEmpty()) {
                continue;
            }

            $numeroOrden = $this->generarNumeroOrden();
            $fechaEntrega = now()->addDays(max(1, (int) ($proveedor->tiempo_entrega_dias ?? 3)))->toDateString();
            $condicionesPago = $proveedor->condiciones_pago
                ?: ('Credito a ' . max(0, (int) ($proveedor->dias_credito ?? 0)) . ' dias');

            $resumenOrden = [
                'numero_orden' => $numeroOrden,
                'proveedor' => $proveedor->nombre_comercial ?: $proveedor->razon_social,
                'insumos' => [],
                'total_cantidad' => 0.0,
                'subtotal' => 0.0,
            ];

            $ordenCompra = null;

            if (! $dryRun) {
                $ordenCompra = OrdenCompra::query()->create([
                    'numero_orden' => $numeroOrden,
                    'proveedor_id' => (int) $proveedorId,
                    'user_id' => $responsableId,
                    'fecha_orden' => now(),
                    'fecha_entrega_prevista' => $fechaEntrega,
                    'estado' => 'Pendiente',
                    'impuestos' => 0,
                    'descuentos' => 0,
                    'costo_flete' => 0,
                    'monto_total' => 0,
                    'notas' => 'Generada automaticamente por stock bajo.',
                    'condiciones_pago' => $condicionesPago,
                    'incoterm' => null,
                ]);
            }

            foreach ($insumosDisponibles->values() as $index => $insumo) {
                $cantidadSolicitada = $this->calcularCantidadSolicitada($insumo);
                $precio = (float) ($insumo->precio_costo ?? $insumo->precio_unitario ?? 0);
                $subtotalLinea = round($cantidadSolicitada * $precio, 4);

                if (! $dryRun && $ordenCompra) {
                    OrdenCompraDetalle::query()->create([
                        'orden_compra_id' => $ordenCompra->id,
                        'numero_linea' => $index + 1,
                        'insumo_id' => $insumo->id,
                        'unidad_medida_id' => (int) $insumo->unidad_medida_id,
                        'cantidad_solicitada' => $cantidadSolicitada,
                        'precio_unitario' => $precio,
                        'descuento_porcentaje' => 0,
                        'subtotal' => $subtotalLinea,
                        'estado_linea' => 'Pendiente',
                        'notas' => 'Sugerida por abastecimiento automatico.',
                    ]);
                }

                $resumenOrden['insumos'][] = [
                    'insumo_id' => $insumo->id,
                    'codigo' => $insumo->codigo_insumo,
                    'nombre' => $insumo->nombre,
                    'stock_actual' => (float) $insumo->stock_actual,
                    'stock_minimo' => (float) $insumo->stock_minimo,
                    'cantidad_solicitada' => $cantidadSolicitada,
                ];
                $resumenOrden['total_cantidad'] += $cantidadSolicitada;
                $resumenOrden['subtotal'] += $subtotalLinea;
            }

            $resumenOrden['total_cantidad'] = round((float) $resumenOrden['total_cantidad'], 4);
            $resumenOrden['subtotal'] = round((float) $resumenOrden['subtotal'], 4);

            if (! $dryRun && $ordenCompra) {
                $ordenCompra->update([
                    'total_items' => count($resumenOrden['insumos']),
                    'total_cantidad' => $resumenOrden['total_cantidad'],
                    'subtotal' => $resumenOrden['subtotal'],
                    'monto_total' => $resumenOrden['subtotal'],
                ]);

                $resultado['ordenes_creadas']++;
                $resultado['detalles_creados'] += count($resumenOrden['insumos']);
            }

            $resultado['ordenes'][] = $resumenOrden;
        }

        return $resultado;
    }

    private function resolverResponsableId(): ?int
    {
        $usuario = User::query()
            ->where('activo', true)
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['super_admin', 'super-admin', 'supervisor_almacen', 'gerente_produccion']);
            })
            ->orderBy('id')
            ->first();

        if ($usuario) {
            return (int) $usuario->id;
        }

        return User::query()
            ->where('activo', true)
            ->orderBy('id')
            ->value('id');
    }

    /**
     * @param Collection<int, Insumo> $insumosProveedor
     */
    private function filtrarInsumosSinOrdenAbierta(Collection $insumosProveedor, int $proveedorId, array &$resultado): Collection
    {
        $ids = $insumosProveedor->pluck('id')->values()->all();

        $insumosConOrdenAbierta = OrdenCompraDetalle::query()
            ->select('ordenes_compra_detalles.insumo_id')
            ->join('ordenes_compra', 'ordenes_compra.id', '=', 'ordenes_compra_detalles.orden_compra_id')
            ->where('ordenes_compra.proveedor_id', $proveedorId)
            ->whereIn('ordenes_compra.estado', ['Pendiente', 'Confirmada'])
            ->whereIn('ordenes_compra_detalles.insumo_id', $ids)
            ->distinct()
            ->pluck('insumo_id')
            ->all();

        return $insumosProveedor->reject(function (Insumo $insumo) use ($insumosConOrdenAbierta, &$resultado): bool {
            $omitido = in_array((int) $insumo->id, $insumosConOrdenAbierta, true);
            if ($omitido) {
                $resultado['insumos_omitidos_por_orden_abierta']++;
            }

            return $omitido;
        });
    }

    private function calcularCantidadSolicitada(Insumo $insumo): float
    {
        $stockActual = (float) $insumo->stock_actual;
        $stockMinimo = (float) $insumo->stock_minimo;
        $cantidadMinOrden = max(1, (int) ($insumo->cantidad_minima_orden ?? 1));

        $objetivo = max($stockMinimo * 2, $stockMinimo + $cantidadMinOrden);
        $cantidad = max((float) $cantidadMinOrden, $objetivo - $stockActual);

        return round($cantidad, 4);
    }

    private function generarNumeroOrden(): string
    {
        do {
            $numeroOrden = 'OC-AUTO-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        } while (OrdenCompra::query()->where('numero_orden', $numeroOrden)->exists());

        return $numeroOrden;
    }
}
