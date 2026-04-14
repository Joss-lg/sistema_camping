<?php

namespace App\Jobs;

use App\Models\Insumo;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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
        /** @var NotificacionSistemaPatternService $notificacionService */
        $notificacionService = app(NotificacionSistemaPatternService::class);

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
            $usuarios = $notificacionService->usuariosActivosPorRoles([
                'SUPER_ADMIN',
                'GERENTE_PRODUCCION',
                'GERENTE_COMPRAS',
            ]);

            if ($usuarios->isNotEmpty()) {
                foreach ($usuarios as $usuario) {
                    $notificacionService->crearSiNoExisteHoy([
                        'titulo' => 'Costos promedio actualizados',
                        'mensaje' => sprintf(
                            'Se recalcularon costos promedio para %d insumo(s) en la ventana reciente de compras.',
                            $cantidadActualizada
                        ),
                        'tipo' => 'Informativa',
                        'modulo' => 'Compras',
                        'prioridad' => 'Media',
                        'user_id' => (int) $usuario->id,
                        'role_id' => (int) $usuario->role_id,
                        'requiere_accion' => false,
                        'url_accion' => '/reportes',
                        'metadata' => [
                            'cantidad_actualizada' => $cantidadActualizada,
                            'origen' => 'job.calcular_costos_promedio',
                        ],
                    ], 'cantidad_actualizada', $cantidadActualizada);
                }
            }
        }
    }
}
