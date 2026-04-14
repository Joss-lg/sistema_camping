<?php

namespace App\Services;

use App\Models\Insumo;

class AbastecimientoResumenOrdenFactory
{
    /**
     * @return array{insumo_id:int,codigo:string,nombre:string,stock_actual:float,stock_minimo:float,cantidad_solicitada:float}
     */
    public function construirLineaResumen(Insumo $insumo, float $cantidadSolicitada): array
    {
        return [
            'insumo_id' => (int) $insumo->id,
            'codigo' => (string) $insumo->codigo_insumo,
            'nombre' => (string) $insumo->nombre,
            'stock_actual' => (float) $insumo->stock_actual,
            'stock_minimo' => (float) $insumo->stock_minimo,
            'cantidad_solicitada' => $cantidadSolicitada,
        ];
    }

    /**
     * @param array<int, array{insumo_id:int,codigo:string,nombre:string,stock_actual:float,stock_minimo:float,cantidad_solicitada:float}> $insumos
     * @param array{numero_orden:string,total_cantidad:float,subtotal:float} $persistencia
     * @return array{numero_orden:string,proveedor:string,insumos:array<int,array{insumo_id:int,codigo:string,nombre:string,stock_actual:float,stock_minimo:float,cantidad_solicitada:float}>,total_cantidad:float,subtotal:float}
     */
    public function construirResumenOrden(string $proveedorNombre, array $insumos, array $persistencia): array
    {
        return [
            'numero_orden' => (string) $persistencia['numero_orden'],
            'proveedor' => $proveedorNombre,
            'insumos' => $insumos,
            'total_cantidad' => (float) $persistencia['total_cantidad'],
            'subtotal' => (float) $persistencia['subtotal'],
        ];
    }
}
