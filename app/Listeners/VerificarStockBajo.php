<?php

namespace App\Listeners;

use App\Events\MaterialConsumido;
use App\Models\Insumo;
use App\Services\StockBajoInsumosNotifier;
use Illuminate\Support\Facades\Log;

class VerificarStockBajo
{
    public function __construct(
        private readonly StockBajoInsumosNotifier $notifier
    ) {
    }

    public function handle(MaterialConsumido $event): void
    {
        try {
            $consumo = $event->consumoMaterial;
            $insumo = Insumo::query()->find($consumo->insumo_id);

            if (! $insumo) {
                return;
            }

            if ((float) $insumo->stock_actual > (float) $insumo->stock_minimo) {
                return;
            }

            $this->notifier->notificar($insumo, 'listener.verificar_stock_bajo');
        } catch (\Throwable $e) {
            Log::error('Listener VerificarStockBajo fallo', [
                'listener' => self::class,
                'consumo_material_id' => (int) ($event->consumoMaterial->id ?? 0),
                'insumo_id' => (int) ($event->consumoMaterial->insumo_id ?? 0),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
