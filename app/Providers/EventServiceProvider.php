<?php

namespace App\Providers;

use App\Events\EtapaCompletada;
use App\Events\MaterialConsumido;
use App\Events\OrdenProduccionCompletada;
use App\Events\OrdenProduccionCreada;
use App\Listeners\ActivarSiguienteEtapa;
use App\Listeners\ActualizarStockInsumo;
use App\Listeners\CrearInventarioProductoTerminado;
use App\Listeners\GenerarEtapasTrazabilidad;
use App\Listeners\LiberarReservaInsumos;
use App\Listeners\VerificarStockBajo;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrdenProduccionCreada::class => [
            GenerarEtapasTrazabilidad::class,
        ],
        MaterialConsumido::class => [
            ActualizarStockInsumo::class,
            VerificarStockBajo::class,
        ],
        EtapaCompletada::class => [
            ActivarSiguienteEtapa::class,
        ],
        OrdenProduccionCompletada::class => [
            CrearInventarioProductoTerminado::class,
            LiberarReservaInsumos::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
