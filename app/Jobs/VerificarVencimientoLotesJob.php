<?php

namespace App\Jobs;

use App\Models\LoteInsumo;
use App\Models\User;
use App\Notifications\LotePorVencerNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

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
        $usuariosDestino = $this->obtenerUsuariosDestino();

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
            ->chunkById(200, function ($lotes) use ($usuariosDestino): void {
                foreach ($lotes as $lote) {
                    $diasRestantes = (int) now()->startOfDay()->diffInDays($lote->fecha_vencimiento, false);

                    Notification::send(
                        $usuariosDestino,
                        new LotePorVencerNotification([
                            'lote_id' => $lote->id,
                            'numero_lote' => $lote->numero_lote,
                            'insumo_id' => $lote->insumo_id,
                            'insumo_nombre' => $lote->insumo?->nombre,
                            'insumo_codigo' => $lote->insumo?->codigo_insumo,
                            'categoria_insumo' => $lote->insumo?->categoriaInsumo?->nombre,
                            'proveedor' => $lote->proveedor?->nombre_comercial ?: $lote->proveedor?->razon_social,
                            'fecha_vencimiento' => optional($lote->fecha_vencimiento)?->toDateString(),
                            'cantidad_en_stock' => (float) $lote->cantidad_en_stock,
                            'estado_calidad' => $lote->estado_calidad,
                            'dias_restantes' => $diasRestantes,
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
                $query->whereRaw('LOWER(slug) = ?', ['gerente-compras'])
                    ->orWhereRaw('LOWER(nombre) = ?', ['gerente de compras'])
                    ->orWhereRaw('LOWER(slug) = ?', ['admin']);
            })
            ->get();
    }
}
