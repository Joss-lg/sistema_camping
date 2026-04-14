<?php

namespace App\Jobs;

use App\Services\AbastecimientoAutomaticoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerarOrdenesCompraAutomaticasJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function handle(AbastecimientoAutomaticoService $service): void
    {
        $resultado = $service->generarOrdenes(false);
        
        if (isset($resultado['error'])) {
            Log::error('GenerarOrdenesCompraAutomaticasJob: Error', [
                'error' => $resultado['error'],
                'detalles' => $resultado,
            ]);
            
            $this->fail(new \Exception($resultado['error']));
        }
        
        Log::info('GenerarOrdenesCompraAutomaticasJob: Completado', [
            'ordenes_creadas' => $resultado['ordenes_creadas'] ?? 0,
            'detalles_creados' => $resultado['detalles_creados'] ?? 0,
        ]);
    }
}
