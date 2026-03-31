<?php

namespace App\Jobs;

use App\Models\Insumo;
use App\Models\User;
use App\Notifications\StockBajoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class VerificarStockBajoJob implements ShouldQueue
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

        Insumo::query()
            ->select([
                'id',
                'codigo_insumo',
                'nombre',
                'stock_actual',
                'stock_minimo',
                'unidad_medida_id',
                'tipo_producto_id',
                'activo',
            ])
            ->with([
                'unidadMedida:id,nombre,abreviatura',
                'tipoProducto:id,nombre',
            ])
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('id')
            ->chunkById(200, function ($insumos) use ($usuariosDestino): void {
                foreach ($insumos as $insumo) {
                    Notification::send(
                        $usuariosDestino,
                        new StockBajoNotification([
                            'insumo_id' => $insumo->id,
                            'codigo_insumo' => $insumo->codigo_insumo,
                            'nombre' => $insumo->nombre,
                            'stock_actual' => (float) $insumo->stock_actual,
                            'stock_minimo' => (float) $insumo->stock_minimo,
                            'unidad_medida' => $insumo->unidadMedida?->abreviatura ?: ($insumo->unidadMedida?->nombre ?? 'u.'),
                            'tipo_producto' => $insumo->tipoProducto?->nombre,
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
                    ->orWhereRaw('LOWER(nombre) = ?', ['gerente de compras']);
            })
            ->get();
    }
}
