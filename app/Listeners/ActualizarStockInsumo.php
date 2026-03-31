<?php

namespace App\Listeners;

use App\Events\MaterialConsumido;
use App\Models\Insumo;
use Illuminate\Support\Facades\DB;

class ActualizarStockInsumo
{
    public function handle(MaterialConsumido $event): void
    {
        $consumo = $event->consumoMaterial;

        DB::transaction(function () use ($consumo): void {
            $insumo = Insumo::query()->lockForUpdate()->find($consumo->insumo_id);

            if (! $insumo) {
                return;
            }

            $cantidadConsumida = (float) $consumo->cantidad_consumida;
            $stockActual = (float) $insumo->stock_actual;
            $stockNuevo = max(0, $stockActual - $cantidadConsumida);

            $insumo->update([
                'stock_actual' => $stockNuevo,
            ]);
        });
    }
}
