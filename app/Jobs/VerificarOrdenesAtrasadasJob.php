<?php

namespace App\Jobs;

use App\Models\OrdenProduccion;
use App\Models\User;
use App\Notifications\OrdenProduccionAtrasadaNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

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
        $usuariosDestino = $this->obtenerUsuariosDestino();

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
            ->chunkById(200, function ($ordenes) use ($usuariosDestino): void {
                foreach ($ordenes as $orden) {
                    Notification::send(
                        $usuariosDestino,
                        new OrdenProduccionAtrasadaNotification([
                            'orden_id' => $orden->id,
                            'numero_orden' => $orden->numero_orden,
                            'estado' => $orden->estado,
                            'fecha_fin_prevista' => optional($orden->fecha_fin_prevista)?->toDateString(),
                            'tipo_producto' => $orden->tipoProducto?->nombre,
                            'responsable' => $orden->user?->name,
                            'porcentaje_completado' => (float) $orden->porcentaje_completado,
                            'prioridad' => $orden->prioridad,
                        ])
                    );
                }
            });
    }

    private function obtenerUsuariosDestino()
    {
        return User::query()
            ->select(['id', 'name', 'email', 'role_id', 'activo'])
            ->with('role:id,nombre,slug')
            ->where('activo', true)
            ->whereHas('role', function ($query): void {
                $query->whereRaw('LOWER(slug) = ?', ['gerente-produccion'])
                    ->orWhereRaw('LOWER(nombre) = ?', ['gerente de produccion'])
                    ->orWhereRaw('LOWER(slug) = ?', ['admin']);
            })
            ->get();
    }
}
