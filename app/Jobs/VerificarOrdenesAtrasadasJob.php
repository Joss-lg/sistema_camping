<?php

namespace App\Jobs;

use App\Models\OrdenProduccion;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarOrdenesAtrasadasJob implements ShouldQueue
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

        $usuariosDestino = $notificacionService->usuariosActivosPorRoles(['GERENTE_PRODUCCION', 'SUPER_ADMIN']);

        if ($usuariosDestino->isEmpty()) {
            return;
        }

        OrdenProduccion::query()
            ->select([
                'id',
                'numero_orden',
                'tipo_producto_id',
                'user_id',
                'fecha_fin_prevista',
                'estado',
                'porcentaje_completado',
                'prioridad',
            ])
            ->with([
                'tipoProducto:id,nombre',
                'user:id,name',
            ])
            ->whereDate('fecha_fin_prevista', '<', now()->toDateString())
            ->whereNotIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS)
            ->orderBy('id')
            ->chunkById(200, function ($ordenes) use ($usuariosDestino, $notificacionService): void {
                foreach ($ordenes as $orden) {
                    foreach ($usuariosDestino as $usuario) {
                        $notificacionService->crearSiNoExisteHoy([
                            'titulo' => 'Orden de producción atrasada',
                            'mensaje' => sprintf(
                                'La orden %s está atrasada. Estado: %s. Fecha fin prevista: %s. Avance: %.2f%%.',
                                (string) ($orden->numero_orden ?: ('#' . $orden->id)),
                                (string) $orden->estado,
                                (string) (optional($orden->fecha_fin_prevista)?->toDateString() ?: 'N/A'),
                                (float) $orden->porcentaje_completado
                            ),
                            'tipo' => 'Alerta',
                            'modulo' => 'Produccion',
                            'prioridad' => 'Alta',
                            'user_id' => (int) $usuario->id,
                            'role_id' => (int) $usuario->role_id,
                            'requiere_accion' => true,
                            'url_accion' => '/produccion',
                            'metadata' => [
                                'orden_id' => (int) $orden->id,
                                'numero_orden' => (string) $orden->numero_orden,
                                'estado' => (string) $orden->estado,
                                'fecha_fin_prevista' => optional($orden->fecha_fin_prevista)?->toDateString(),
                                'tipo_producto' => (string) ($orden->tipoProducto?->nombre ?: ''),
                                'responsable' => (string) ($orden->user?->name ?: ''),
                                'porcentaje_completado' => (float) $orden->porcentaje_completado,
                                'prioridad_orden' => (string) ($orden->prioridad ?: ''),
                                'origen' => 'job.verificar_ordenes_atrasadas',
                            ],
                        ], 'orden_id', (int) $orden->id);
                    }
                }
            });
    }
}
