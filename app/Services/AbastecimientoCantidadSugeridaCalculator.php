<?php

namespace App\Services;

use App\Models\Insumo;

class AbastecimientoCantidadSugeridaCalculator
{
    public function calcular(Insumo $insumo): float
    {
        $stockActual = (float) $insumo->stock_actual;
        $stockMinimo = (float) $insumo->stock_minimo;
        $cantidadMinOrden = max(1, (int) ($insumo->cantidad_minima_orden ?? 1));

        $objetivo = max($stockMinimo * 2, $stockMinimo + $cantidadMinOrden);
        $cantidad = max((float) $cantidadMinOrden, $objetivo - $stockActual);

        return round($cantidad, 4);
    }
}
