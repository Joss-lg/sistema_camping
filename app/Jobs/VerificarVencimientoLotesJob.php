<?php

namespace App\Jobs;

use App\Models\LoteInsumo;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarVencimientoLotesJob implements ShouldQueue
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

        $usuariosDestino = $notificacionService->usuariosActivosPorRoles(['GERENTE_COMPRAS', 'SUPER_ADMIN']);

        if ($usuariosDestino->isEmpty()) {
            return;
        }

        $fechaInicio = now()->toDateString();
        $fechaLimite = now()->addDays(30)->toDateString();

        LoteInsumo::query()
            ->select([
                'id',
                'numero_lote',
                'insumo_id',
                'proveedor_id',
                'fecha_vencimiento',
                'cantidad_en_stock',
                'estado_calidad',
                'activo',
            ])
            ->with([
                'insumo:id,nombre,codigo_insumo,categoria_insumo_id',
                'insumo.categoriaInsumo:id,nombre',
                'proveedor:id,nombre_comercial,razon_social',
            ])
            ->where('activo', true)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', $fechaInicio)
            ->whereDate('fecha_vencimiento', '<=', $fechaLimite)
            ->whereRaw('cantidad_en_stock > 0')
            ->orderBy('id')
            ->chunkById(200, function ($lotes) use ($usuariosDestino, $notificacionService): void {
                foreach ($lotes as $lote) {
                    $diasRestantes = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);

                    foreach ($usuariosDestino as $usuario) {
                        $notificacionService->crearSiNoExisteHoy([
                            'titulo' => 'Lote por vencer',
                            'mensaje' => sprintf(
                                'El lote %s del insumo %s vence en %d día(s). Stock: %.4f.',
                                (string) $lote->numero_lote,
                                (string) ($lote->insumo?->nombre ?: 'Insumo'),
                                $diasRestantes,
                                (float) $lote->cantidad_en_stock
                            ),
                            'tipo' => 'Alerta',
                            'modulo' => 'Compras',
                            'prioridad' => 'Alta',
                            'user_id' => (int) $usuario->id,
                            'role_id' => (int) $usuario->role_id,
                            'requiere_accion' => true,
                            'url_accion' => '/insumos-bajo-stock',
                            'metadata' => [
                                'lote_id' => (int) $lote->id,
                                'numero_lote' => (string) $lote->numero_lote,
                                'insumo_id' => (int) $lote->insumo_id,
                                'insumo_nombre' => (string) ($lote->insumo?->nombre ?: ''),
                                'insumo_codigo' => (string) ($lote->insumo?->codigo_insumo ?: ''),
                                'categoria_insumo' => (string) ($lote->insumo?->categoriaInsumo?->nombre ?: ''),
                                'proveedor' => (string) ($lote->proveedor?->nombre_comercial ?: $lote->proveedor?->razon_social ?: ''),
                                'fecha_vencimiento' => optional($lote->fecha_vencimiento)?->toDateString(),
                                'cantidad_en_stock' => (float) $lote->cantidad_en_stock,
                                'estado_calidad' => (string) ($lote->estado_calidad ?: ''),
                                'dias_restantes' => $diasRestantes,
                                'origen' => 'job.verificar_vencimiento_lotes',
                            ],
                        ], 'lote_id', (int) $lote->id);
                    }
                }
            });
    }
}
