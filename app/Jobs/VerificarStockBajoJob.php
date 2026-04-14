<?php

namespace App\Jobs;

use App\Models\Insumo;
use App\Services\StockBajoInsumosNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarStockBajoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(StockBajoInsumosNotifier $notifier): void
    {
        Insumo::query()
            ->select([
                'id',
                'codigo_insumo',
                'nombre',
                'stock_actual',
                'stock_minimo',
                'unidad_medida_id',
                'tipo_producto_id',
                'activo',
            ])
            ->with([
                'unidadMedida:id,nombre,abreviatura',
                'tipoProducto:id,nombre',
            ])
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('id')
            ->chunkById(200, function ($insumos) use ($notifier): void {
                foreach ($insumos as $insumo) {
                    $notifier->notificar($insumo, 'job.verificar_stock_bajo_diario');
                }
            });
    }
}
