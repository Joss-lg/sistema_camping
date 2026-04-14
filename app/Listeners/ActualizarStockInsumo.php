<?php

namespace App\Listeners;

use App\Events\MaterialConsumido;
use App\Models\Insumo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarStockInsumo
{
    public function handle(MaterialConsumido $event): void
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('Listener ActualizarStockInsumo fallo', [
                'listener' => self::class,
                'consumo_material_id' => (int) ($event->consumoMaterial->id ?? 0),
                'insumo_id' => (int) ($event->consumoMaterial->insumo_id ?? 0),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
