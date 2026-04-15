<?php

namespace App\Jobs;

use App\Models\InventarioProductoTerminado;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarPendientesInspeccionTerminadosJob implements ShouldQueue
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

        $pendientesBaseQuery = InventarioProductoTerminado::query()
            ->where('estado', InventarioProductoTerminado::ESTADO_PENDIENTE_INSPECCION)
            ->whereRaw('cantidad_en_almacen > 0');

        $totalPendientes = (clone $pendientesBaseQuery)->count();

        if ($totalPendientes <= 0) {
            return;
        }

        $muestraPendientes = (clone $pendientesBaseQuery)
            ->with([
                'tipoProducto:id,nombre',
                'ubicacionAlmacen:id,nombre',
            ])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $resumen = $muestraPendientes
            ->map(function (InventarioProductoTerminado $item): string {
                $producto = (string) ($item->tipoProducto?->nombre ?: 'Producto terminado');
                $ubicacion = (string) ($item->ubicacionAlmacen?->nombre ?: 'Almacen');

                return sprintf('%s en %s', $producto, $ubicacion);
            })
            ->implode(', ');

        foreach ($destinatarios as $usuario) {
            $notificacionService->crearSiNoExisteHoy([
                'titulo' => 'Productos pendientes de inspeccion',
                'mensaje' => sprintf(
                    'Hay %d registro(s) de producto terminado pendientes de inspeccion. Muestra: %s.',
                    $totalPendientes,
                    $resumen !== '' ? $resumen : 'Sin detalle'
                ),
                'tipo' => 'Alerta',
                'modulo' => 'Terminados',
                'prioridad' => 'Alta',
                'user_id' => (int) $usuario->id,
                'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                'estado' => 'Pendiente',
                'fecha_programada' => now(),
                'requiere_accion' => true,
                'url_accion' => '/terminados',
                'metadata' => [
                    'tipo_alerta' => 'pendientes_inspeccion_terminados',
                    'total_pendientes' => (int) $totalPendientes,
                    'muestra' => $muestraPendientes->pluck('id')->values()->all(),
                    'origen' => 'job.verificar_pendientes_inspeccion_terminados',
                ],
            ], 'tipo_alerta', 'pendientes_inspeccion_terminados');
        }
    }
}
