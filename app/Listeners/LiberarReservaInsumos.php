<?php

namespace App\Listeners;

use App\Events\OrdenProduccionCompletada;
use App\Services\ReservaInsumosService;
use Illuminate\Support\Facades\Log;

class LiberarReservaInsumos
{
    public function __construct(
        private readonly ReservaInsumosService $reservaService
    ) {
    }

    public function handle(OrdenProduccionCompletada $event): void
    {
        try {
            $this->reservaService->liberar($event->ordenProduccion);
        } catch (\Throwable $e) {
            Log::error('LiberarReservaInsumos: error al liberar reserva de materiales.', [
                'orden_produccion_id' => (int) ($event->ordenProduccion->id ?? 0),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
