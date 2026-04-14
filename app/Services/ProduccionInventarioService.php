<?php

namespace App\Services;

use App\Models\InventarioProductoTerminado;
use App\Models\ProductoTerminado;

class ProduccionInventarioService
{
    public function ocultarTerminadosDeOrdenReabierta(int $ordenId): void
    {
        $productoIds = ProductoTerminado::query()
            ->where('orden_produccion_id', $ordenId)
            ->pluck('id');

        if ($productoIds->isEmpty()) {
            return;
        }

        InventarioProductoTerminado::query()
            ->whereIn('producto_terminado_id', $productoIds)
            ->delete();

        ProductoTerminado::query()
            ->whereIn('id', $productoIds)
            ->delete();
    }
}
