<?php

namespace App\Jobs;

use App\Models\Insumo;
use App\Models\User;
use App\Notifications\CostosPromedioCalculadosNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CalcularCostosPromedioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        // Se toma una ventana reciente de compras recibidas para reflejar costo vigente.
        $desde = now()->subDays(90)->toDateString();
        $cantidadActualizada = 0;

        DB::table('ordenes_compra_detalles as d')
            ->join('ordenes_compra as o', 'o.id', '=', 'd.orden_compra_id')
            ->selectRaw('d.insumo_id')
            ->selectRaw('SUM(d.cantidad_aceptada) as total_cantidad_aceptada')
            ->selectRaw('SUM((d.precio_unitario * (1 - (d.descuento_porcentaje / 100))) * d.cantidad_aceptada) as costo_total_ponderado')
            ->whereNull('d.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('o.estado', 'Recibida')
            ->whereIn('d.estado_linea', ['Recibida', 'Aceptada'])
            ->whereRaw('d.cantidad_aceptada > 0')
            ->whereDate('o.fecha_entrega_real', '>=', $desde)
            ->groupBy('d.insumo_id')
            ->orderBy('d.insumo_id')
            ->chunk(300, function ($rows) use (&$cantidadActualizada): void {
                $ids = $rows->pluck('insumo_id')->filter()->values();

                if ($ids->isEmpty()) {
                    return;
                }

                $insumos = Insumo::query()
                    ->select(['id', 'precio_costo'])
                    ->whereIn('id', $ids)
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $insumoId = (int) $row->insumo_id;
                    $cantidad = (float) $row->total_cantidad_aceptada;
                    $costoTotal = (float) $row->costo_total_ponderado;

                    if ($insumoId <= 0 || $cantidad <= 0 || ! isset($insumos[$insumoId])) {
                        continue;
                    }

                    $costoPromedio = round($costoTotal / $cantidad, 4);

                    $insumo = $insumos[$insumoId];
                    $insumo->precio_costo = $costoPromedio;
                    $insumo->save();
                    $cantidadActualizada++;
                }
            });

        // Notificar a gerentes de compras y admin
        if ($cantidadActualizada > 0) {
            $usuarios = User::query()
                ->select(['id'])
                ->whereHas('roles', function ($query) {
                    $query->whereIn('slug', ['gerente-compras', 'admin'])
                        ->orWhereIn('name', ['gerente de compras', 'admin']);
                })
                ->get();

            if ($usuarios->isNotEmpty()) {
                Notification::send(
                    $usuarios,
                    new CostosPromedioCalculadosNotification($cantidadActualizada)
                );
            }
        }
    }
}
