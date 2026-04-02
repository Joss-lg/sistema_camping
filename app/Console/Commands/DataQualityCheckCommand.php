<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataQualityCheckCommand extends Command
{
    protected $signature = 'data:quality:check {--strict : Return non-zero exit code when warnings are found}';

    protected $description = 'Checks core domain tables for data integrity anomalies';

    public function handle(): int
    {
        $warnings = [];
        $errors = [];

        if (Schema::hasTable('insumos')) {
            $negativeInsumos = DB::table('insumos')
                ->where(function ($query): void {
                    $query->where('stock_actual', '<', 0)
                        ->orWhere('stock_reservado', '<', 0)
                        ->orWhere('precio_unitario', '<', 0);
                })
                ->count();

            if ($negativeInsumos > 0) {
                $errors[] = "Insumos with negative values: {$negativeInsumos}";
            }
        }

        if (Schema::hasTable('inventario_productos_terminados')) {
            $invalidTerminadoInventory = DB::table('inventario_productos_terminados')
                ->where(function ($query): void {
                    $query->where('cantidad_en_almacen', '<', 0)
                        ->orWhere('cantidad_reservada', '<', 0)
                        ->orWhere('precio_unitario', '<', 0);
                })
                ->count();

            if ($invalidTerminadoInventory > 0) {
                $errors[] = "Inventario de terminados con valores negativos: {$invalidTerminadoInventory}";
            }
        }

        if (Schema::hasTable('ordenes_produccion')) {
            $invalidDates = DB::table('ordenes_produccion')
                ->whereColumn('fecha_fin_prevista', '<', 'fecha_inicio_prevista')
                ->count();

            if ($invalidDates > 0) {
                $errors[] = "Ordenes de produccion con fechas inconsistentes: {$invalidDates}";
            }
        }

        if (Schema::hasTable('notificaciones_sistema')) {
            $stalePendingNotifications = DB::table('notificaciones_sistema')
                ->where('estado', 'Pendiente')
                ->where('created_at', '<=', now()->subDays(7))
                ->count();

            if ($stalePendingNotifications > 0) {
                $warnings[] = "Notificaciones pendientes con mas de 7 dias: {$stalePendingNotifications}";
            }
        }

        $this->info('Data quality check');

        if (empty($errors) && empty($warnings)) {
            $this->line('Status: OK');
            return self::SUCCESS;
        }

        foreach ($errors as $error) {
            $this->error("ERROR: {$error}");
        }

        foreach ($warnings as $warning) {
            $this->warn("WARN: {$warning}");
        }

        if (! empty($errors)) {
            $this->line('Status: FAIL');
            return self::FAILURE;
        }

        $this->line('Status: WARN');

        if ($this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
