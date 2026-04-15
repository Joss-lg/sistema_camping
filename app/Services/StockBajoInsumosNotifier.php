<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\User;
use Illuminate\Support\Collection;

class StockBajoInsumosNotifier
{
    public function __construct(
        private readonly NotificacionSistemaPatternService $notificacionService
    ) {
    }

    public function notificar(Insumo $insumo, string $origen): void
    {
        if ((float) $insumo->stock_actual > (float) $insumo->stock_minimo) {
            return;
        }

        $titulo = 'Alerta de stock bajo: ' . (string) $insumo->codigo_insumo;
        $mensaje = sprintf(
            'El insumo %s (%s) cayo por debajo del minimo. Stock actual: %s, minimo: %s.',
            (string) $insumo->nombre,
            (string) $insumo->codigo_insumo,
            (string) $insumo->stock_actual,
            (string) $insumo->stock_minimo
        );

        $destinatarios = $this->resolverUsuariosDestino();

        if ($destinatarios->isNotEmpty()) {
            foreach ($destinatarios as $user) {
                $this->notificacionService->crearSiNoExisteHoy([
                    'titulo' => $titulo,
                    'mensaje' => $mensaje,
                    'tipo' => 'Advertencia',
                    'modulo' => 'Insumos',
                    'prioridad' => 'Alta',
                    'user_id' => (int) $user->id,
                    'role_id' => (int) $user->role_id,
                    'estado' => 'Pendiente',
                    'fecha_programada' => now(),
                    'requiere_accion' => true,
                    'url_accion' => '/insumos-bajo-stock',
                    'metadata' => [
                        'insumo_id' => (int) $insumo->id,
                        'codigo_insumo' => (string) $insumo->codigo_insumo,
                        'origen' => $origen,
                    ],
                ], 'insumo_id', (int) $insumo->id);
            }

            return;
        }

        $roleIdFallback = PermisoService::resolveRoleByInput('SUPER_ADMIN')?->id
            ?: PermisoService::resolveRoleByInput('ADMIN')?->id;

        if (! $roleIdFallback) {
            $roleIdFallback = \App\Models\Role::query()->orderBy('id')->value('id');
        }

        if (! $roleIdFallback) {
            return;
        }

        $this->notificacionService->crearSiNoExisteHoy([
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => 'Advertencia',
            'modulo' => 'Insumos',
            'prioridad' => 'Alta',
            'user_id' => null,
            'role_id' => (int) $roleIdFallback,
            'estado' => 'Pendiente',
            'fecha_programada' => now(),
            'requiere_accion' => true,
            'url_accion' => '/insumos-bajo-stock',
            'metadata' => [
                'insumo_id' => (int) $insumo->id,
                'codigo_insumo' => (string) $insumo->codigo_insumo,
                'origen' => $origen,
            ],
        ], 'insumo_id', (int) $insumo->id);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolverUsuariosDestino(): Collection
    {
        return $this->notificacionService->usuariosActivos();
    }
}
