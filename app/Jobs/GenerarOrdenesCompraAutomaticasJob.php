<?php

namespace App\Jobs;

use App\Services\AbastecimientoAutomaticoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerarOrdenesCompraAutomaticasJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function handle(AbastecimientoAutomaticoService $service): void
    {
        $service->generarOrdenes(false);
    }
}
