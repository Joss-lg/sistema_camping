<?php

namespace App\Jobs;

use App\Models\InventarioProductoTerminado;
use App\Models\User;
use App\Services\NotificacionSistemaPatternService;
use App\Services\PermisoService;
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
        /** @var NotificacionSistemaPatternService $notificacionService */
        $notificacionService = app(NotificacionSistemaPatternService::class);

        $inventarios = InventarioProductoTerminado::query()
            ->with([
                'tipoProducto:id,nombre,slug,stock_minimo_terminado',
                'ubicacionAlmacen:id,nombre,codigo_ubicacion',
            ])
            ->enAlmacen()
            ->whereRaw('cantidad_en_almacen > 0')
            ->orderBy('id')
            ->get();

        if ($inventarios->isEmpty()) {
            return;
        }

        $rolesPermitidos = ['SUPER_ADMIN', 'SUPERVISOR_ALMACEN', 'GERENTE_PRODUCCION'];

        $destinatarios = User::query()
            ->where('activo', true)
            ->with('role:id,nombre,slug')
            ->get(['id', 'role_id'])
            ->filter(function (User $user) use ($rolesPermitidos): bool {
                $roleKey = PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre ?: ''));

                return in_array($roleKey, $rolesPermitidos, true);
            })
            ->values();

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
                $notificacionService->crearSiNoExisteHoy([
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
                ], 'inventario_id', (int) $inventario->id);
            }
        }
    }
}
