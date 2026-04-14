<?php

use App\Jobs\CalcularCostosPromedioJob;
use App\Jobs\GenerarOrdenesCompraAutomaticasJob;
use App\Jobs\VerificarOrdenesAtrasadasJob;
use App\Jobs\VerificarStockBajoJob;
use App\Jobs\VerificarStockBajoTerminadosJob;
use App\Jobs\VerificarVencimientoLotesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new VerificarStockBajoJob())
    ->name('verificar-stock-bajo-diario')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::job(new VerificarStockBajoTerminadosJob())
    ->name('verificar-stock-bajo-terminados')
    ->dailyAt('08:05');

Schedule::job(new GenerarOrdenesCompraAutomaticasJob())
    ->name('generar-ordenes-compra-automaticas')
    ->everyFourHours();

Schedule::job(new VerificarOrdenesAtrasadasJob())
    ->name('verificar-ordenes-atrasadas-diario')
    ->dailyAt('08:10');

Schedule::job(new VerificarVencimientoLotesJob())
    ->name('verificar-vencimiento-lotes-diario')
    ->dailyAt('08:20');

Schedule::job(new CalcularCostosPromedioJob())
    ->name('calcular-costos-promedio-semanal')
    ->weeklyOn(1, '08:30');

Schedule::command('reportes:expirar --cleanup-days=30')
    ->name('expirar-y-limpiar-reportes-generados')
    ->dailyAt('02:30')
    ->withoutOverlapping();
