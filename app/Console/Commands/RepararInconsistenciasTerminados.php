<?php

namespace App\Console\Commands;

use App\Events\OrdenProduccionCompletada;
use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepararInconsistenciasTerminados extends Command
{
    protected $signature = 'terminados:reparar-inconsistencias
                            {--dry-run : Solo muestra impactos sin modificar datos}
                            {--limit=100 : Maximo de ordenes finalizadas a sincronizar}';

    protected $description = 'Repara inconsistencias del modulo de terminados bajo demanda y con trazabilidad.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));

        $productoIds = ProductoTerminado::query()
            ->whereHas('ordenProduccion', function ($query): void {
                $query->whereNotIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS);
            })
            ->pluck('id');

        $ordenesPendientes = OrdenProduccion::query()
            ->whereIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS)
            ->where(function ($query): void {
                $query->doesntHave('productosTerminados')
                    ->orWhereHas('productosTerminados', function ($subQuery): void {
                        $subQuery->doesntHave('inventario');
                    });
            })
            ->limit($limit)
            ->get();

        $inventariosEliminar = $productoIds->isEmpty()
            ? 0
            : InventarioProductoTerminado::query()->whereIn('producto_terminado_id', $productoIds)->count();

        $resumen = [
            'dry_run' => $dryRun,
            'limit' => $limit,
            'productos_huerfanos' => $productoIds->count(),
            'inventarios_huerfanos' => $inventariosEliminar,
            'ordenes_a_sincronizar' => $ordenesPendientes->count(),
            'orden_ids' => $ordenesPendientes->pluck('id')->values()->all(),
        ];

        if (! $dryRun) {
            DB::transaction(function () use ($productoIds): void {
                if ($productoIds->isEmpty()) {
                    return;
                }

                InventarioProductoTerminado::query()
                    ->whereIn('producto_terminado_id', $productoIds)
                    ->delete();

                ProductoTerminado::query()
                    ->whereIn('id', $productoIds)
                    ->delete();
            });

            foreach ($ordenesPendientes as $orden) {
                event(new OrdenProduccionCompletada($orden));
            }
        }

        Log::info('terminados:reparar-inconsistencias ejecutado', $resumen);

        $this->info($dryRun
            ? 'Dry-run completado. No se aplicaron cambios.'
            : 'Reparacion completada.');
        $this->line('Productos huerfanos detectados: ' . $resumen['productos_huerfanos']);
        $this->line('Inventarios huerfanos detectados: ' . $resumen['inventarios_huerfanos']);
        $this->line('Ordenes a sincronizar: ' . $resumen['ordenes_a_sincronizar']);

        return self::SUCCESS;
    }
}
