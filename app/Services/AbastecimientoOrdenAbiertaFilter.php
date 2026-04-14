<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\OrdenCompraDetalle;
use Illuminate\Support\Collection;

class AbastecimientoOrdenAbiertaFilter
{
    /**
     * @param Collection<int, Insumo> $insumosProveedor
     * @param array<int, string> $estadosAbiertos
     * @return array{insumos: Collection<int, Insumo>, omitidos: int}
     */
    public function filtrar(Collection $insumosProveedor, int $proveedorId, array $estadosAbiertos): array
    {
        $ids = $insumosProveedor->pluck('id')->values()->all();

        $insumosConOrdenAbierta = OrdenCompraDetalle::query()
            ->select('ordenes_compra_detalles.insumo_id')
            ->join('ordenes_compra', 'ordenes_compra.id', '=', 'ordenes_compra_detalles.orden_compra_id')
            ->where('ordenes_compra.proveedor_id', $proveedorId)
            ->whereIn('ordenes_compra.estado', $estadosAbiertos)
            ->whereIn('ordenes_compra_detalles.insumo_id', $ids)
            ->distinct()
            ->pluck('insumo_id')
            ->all();

        $omitidos = 0;

        $insumos = $insumosProveedor->reject(function (Insumo $insumo) use ($insumosConOrdenAbierta, &$omitidos): bool {
            $omitido = in_array((int) $insumo->id, $insumosConOrdenAbierta, true);

            if ($omitido) {
                $omitidos++;
            }

            return $omitido;
        });

        return [
            'insumos' => $insumos,
            'omitidos' => $omitidos,
        ];
    }
}
