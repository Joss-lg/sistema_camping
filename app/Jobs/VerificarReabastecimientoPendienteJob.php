<?php

namespace App\Jobs;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarReabastecimientoPendienteJob implements ShouldQueue
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

    public function handle(): void
    {
        /** @var NotificacionSistemaPatternService $notificacionService */
        $notificacionService = app(NotificacionSistemaPatternService::class);

        $destinatarios = $notificacionService->usuariosActivos();

        if ($destinatarios->isEmpty()) {
            return;
        }

        $faltantesBaseQuery = Insumo::query()
            ->select([
                'id',
                'codigo_insumo',
                'nombre',
                'stock_actual',
                'stock_minimo',
                'activo',
            ])
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->whereDoesntHave('ordenesCompraDetalles', function ($detalleQuery): void {
                $detalleQuery
                    ->whereRaw('cantidad_solicitada > cantidad_aceptada')
                    ->whereHas('ordenCompra', function ($ordenQuery): void {
                        $ordenQuery->whereIn('estado', [
                            OrdenCompra::ESTADO_PENDIENTE,
                            OrdenCompra::ESTADO_CONFIRMADA,
                        ]);
                    });
            });

        $totalFaltantesSinOrden = (clone $faltantesBaseQuery)->count();

        if ($totalFaltantesSinOrden <= 0) {
            return;
        }

        $muestra = (clone $faltantesBaseQuery)
            ->orderBy('id')
            ->limit(5)
            ->get();

        $resumen = $muestra
            ->map(function (Insumo $insumo): string {
                return sprintf(
                    '%s (%s: %.2f / min %.2f)',
                    (string) $insumo->nombre,
                    (string) $insumo->codigo_insumo,
                    (float) $insumo->stock_actual,
                    (float) $insumo->stock_minimo
                );
            })
            ->implode(', ');

        foreach ($destinatarios as $usuario) {
            $notificacionService->crearSiNoExisteHoy([
                'titulo' => 'Reabastecimiento pendiente por solicitar',
                'mensaje' => sprintf(
                    'Hay %d insumo(s) bajo minimo sin orden de compra activa. Muestra: %s.',
                    $totalFaltantesSinOrden,
                    $resumen !== '' ? $resumen : 'Sin detalle'
                ),
                'tipo' => 'Alerta',
                'modulo' => 'Compras',
                'prioridad' => 'Alta',
                'user_id' => (int) $usuario->id,
                'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                'estado' => 'Pendiente',
                'fecha_programada' => now(),
                'requiere_accion' => true,
                'url_accion' => '/ordenes-compra/create',
                'metadata' => [
                    'tipo_alerta' => 'reabastecimiento_pendiente',
                    'total_faltantes' => (int) $totalFaltantesSinOrden,
                    'muestra' => $muestra->pluck('id')->values()->all(),
                    'origen' => 'job.verificar_reabastecimiento_pendiente',
                ],
            ], 'tipo_alerta', 'reabastecimiento_pendiente');
        }
    }
}
