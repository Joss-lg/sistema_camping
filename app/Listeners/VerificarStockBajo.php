<?php

namespace App\Listeners;

use App\Events\MaterialConsumido;
use App\Models\Insumo;
use App\Models\NotificacionSistema;
use App\Models\Role;

class VerificarStockBajo
{
    public function handle(MaterialConsumido $event): void
    {
        $consumo = $event->consumoMaterial;
        $insumo = Insumo::query()->find($consumo->insumo_id);

        if (! $insumo) {
            return;
        }

        if ((float) $insumo->stock_actual > (float) $insumo->stock_minimo) {
            return;
        }

        $roleId = Role::query()
            ->whereIn('slug', ['admin', 'super_admin'])
            ->value('id');

        $titulo = 'Alerta de stock bajo: ' . $insumo->codigo_insumo;
        $mensaje = sprintf(
            'El insumo %s (%s) cayó por debajo del mínimo. Stock actual: %s, mínimo: %s.',
            $insumo->nombre,
            $insumo->codigo_insumo,
            $insumo->stock_actual,
            $insumo->stock_minimo
        );

        $existePendiente = NotificacionSistema::query()
            ->where('modulo', 'Insumos')
            ->where('estado', 'Pendiente')
            ->where('titulo', $titulo)
            ->exists();

        if ($existePendiente) {
            return;
        }

        NotificacionSistema::create([
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => 'Advertencia',
            'modulo' => 'Insumos',
            'prioridad' => 'Alta',
            'user_id' => null,
            'role_id' => $roleId,
            'estado' => 'Pendiente',
            'fecha_programada' => now(),
            'requiere_accion' => true,
            'url_accion' => '/insumos-bajo-stock',
            'metadata' => [
                'insumo_id' => $insumo->id,
                'codigo_insumo' => $insumo->codigo_insumo,
                'origen' => 'listener.verificar_stock_bajo',
            ],
        ]);
    }
}
