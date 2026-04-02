<?php

namespace App\Jobs;

use App\Models\InventarioProductoTerminado;
use App\Models\NotificacionSistema;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerificarStockBajoTerminadosJob implements ShouldQueue
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
        $inventarios = InventarioProductoTerminado::query()
            ->with([
                'tipoProducto:id,nombre,slug,stock_minimo_terminado',
                'ubicacionAlmacen:id,nombre,codigo_ubicacion',
            ])
            ->where('estado', 'En Almacén')
            ->whereRaw('cantidad_en_almacen > 0')
            ->orderBy('id')
            ->get();

        if ($inventarios->isEmpty()) {
            return;
        }

        $destinatarios = User::query()
            ->where('activo', true)
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['super_admin', 'super-admin', 'supervisor_almacen', 'gerente_produccion']);
            })
            ->get(['id', 'role_id']);

        if ($destinatarios->isEmpty()) {
            return;
        }

        foreach ($inventarios as $inventario) {
            $minimo = (float) ($inventario->tipoProducto?->stock_minimo_terminado ?? 5);
            $stock = (float) $inventario->cantidad_en_almacen;

            if ($stock > $minimo) {
                continue;
            }

            foreach ($destinatarios as $destinatario) {
                $duplicada = NotificacionSistema::query()
                    ->where('tipo', 'Alerta')
                    ->where('modulo', 'Terminados')
                    ->where('user_id', $destinatario->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->where('metadata->inventario_id', $inventario->id)
                    ->exists();

                if ($duplicada) {
                    continue;
                }

                NotificacionSistema::query()->create([
                    'titulo' => 'Reabastecimiento de terminados',
                    'mensaje' => sprintf(
                        'Stock bajo del producto terminado %s en %s: %.2f (mínimo %.2f).',
                        $inventario->tipoProducto?->nombre ?? 'Producto terminado',
                        $inventario->ubicacionAlmacen?->nombre ?? 'Almacén',
                        $stock,
                        $minimo
                    ),
                    'tipo' => 'Alerta',
                    'modulo' => 'Terminados',
                    'prioridad' => 'Alta',
                    'user_id' => $destinatario->id,
                    'role_id' => $destinatario->role_id,
                    'estado' => 'Pendiente',
                    'fecha_programada' => now(),
                    'requiere_accion' => true,
                    'url_accion' => '/terminados',
                    'metadata' => [
                        'inventario_id' => $inventario->id,
                        'tipo_producto_id' => $inventario->tipo_producto_id,
                        'stock_actual' => $stock,
                        'stock_minimo' => $minimo,
                        'origen' => 'job.verificar_stock_bajo_terminados',
                    ],
                ]);
            }
        }
    }
}
