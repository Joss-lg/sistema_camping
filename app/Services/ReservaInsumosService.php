<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\OrdenProduccion;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaInsumosService
{
    public function __construct(
        private readonly NotificacionSistemaPatternService $notificaciones
    ) {
    }

    /**
     * Reserva los materiales de la BOM para la orden de producción.
     *
     * Incrementa stock_reservado en cada insumo según la cantidad planificada.
     * Si el stock disponible (actual - reservado) queda por debajo del mínimo,
     * genera una alerta de reabastecimiento para el área de compras.
     */
    public function reservar(OrdenProduccion $orden): void
    {
        $agrupados = $orden->materiales()
            ->where('estado_asignacion', '!=', 'cancelado')
            ->get()
            ->groupBy('insumo_id');

        if ($agrupados->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($agrupados, $orden): void {
            foreach ($agrupados as $insumoId => $lineas) {
                $cantidadReservar = (float) $lineas->sum(fn ($l) => (float) $l->cantidad_necesaria);

                if ($cantidadReservar <= 0) {
                    continue;
                }

                $insumo = Insumo::query()->lockForUpdate()->find($insumoId);

                if (! $insumo) {
                    continue;
                }

                $insumo->stock_reservado = (float) $insumo->stock_reservado + $cantidadReservar;
                $insumo->save();

                $disponible = (float) $insumo->stock_actual - (float) $insumo->stock_reservado;

                if ($disponible < (float) $insumo->stock_minimo) {
                    $this->notificarStockInsuficiente($insumo, $orden, $cantidadReservar, $disponible);
                }
            }
        });
    }

    /**
     * Libera la reserva de materiales de la orden.
     *
     * Resta stock_reservado según cantidad_necesaria de cada línea de material.
     * Debe llamarse tanto al cancelar como al finalizar la orden
     * (en finalización el stock_actual ya fue descontado por los consumos reales).
     */
    public function liberar(OrdenProduccion $orden): void
    {
        $agrupados = $orden->materiales()
            ->get()
            ->groupBy('insumo_id');

        if ($agrupados->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($agrupados): void {
            foreach ($agrupados as $insumoId => $lineas) {
                $cantidadLiberar = (float) $lineas->sum(fn ($l) => (float) $l->cantidad_necesaria);

                if ($cantidadLiberar <= 0) {
                    continue;
                }

                $insumo = Insumo::query()->lockForUpdate()->find($insumoId);

                if (! $insumo) {
                    continue;
                }

                $insumo->stock_reservado = max(0.0, (float) $insumo->stock_reservado - $cantidadLiberar);
                $insumo->save();
            }
        });
    }

    private function notificarStockInsuficiente(
        Insumo $insumo,
        OrdenProduccion $orden,
        float $cantidadReservada,
        float $disponible
    ): void {
        $titulo = 'Stock insuficiente para producción: ' . (string) $insumo->codigo_insumo;
        $mensaje = sprintf(
            'La orden %s reservó %.2f de %s pero el stock disponible quedó en %.2f (mínimo: %.2f). Genere una orden de compra.',
            (string) $orden->numero_orden,
            $cantidadReservada,
            (string) $insumo->nombre,
            $disponible,
            (float) $insumo->stock_minimo
        );

        $destinatarios = $this->notificaciones->usuariosActivos();

        if ($destinatarios->isNotEmpty()) {
            foreach ($destinatarios as $usuario) {
                $this->notificaciones->crearSiNoExisteHoy([
                    'titulo' => $titulo,
                    'mensaje' => $mensaje,
                    'tipo' => 'Advertencia',
                    'modulo' => 'Produccion',
                    'prioridad' => 'Alta',
                    'user_id' => (int) $usuario->id,
                    'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                    'estado' => 'Pendiente',
                    'fecha_programada' => now(),
                    'requiere_accion' => true,
                    'url_accion' => route('ordenes-compra.create', ['reabastecer_insumo_id' => $insumo->id]),
                    'metadata' => [
                        'insumo_id' => (int) $insumo->id,
                        'codigo_insumo' => (string) $insumo->codigo_insumo,
                        'orden_produccion_id' => (int) $orden->id,
                        'stock_disponible' => $disponible,
                        'origen' => 'reserva-produccion',
                    ],
                ], 'insumo_id', (int) $insumo->id);
            }

            return;
        }

        $roleId = PermisoService::resolveRoleByInput('ADMINISTRADOR')?->id
            ?? Role::query()->orderBy('id')->value('id');

        if (! $roleId) {
            Log::warning('ReservaInsumosService: sin destinatarios activos para notificación de stock insuficiente.', [
                'insumo_id' => (int) $insumo->id,
                'orden_id' => (int) $orden->id,
            ]);

            return;
        }

        $this->notificaciones->crearSiNoExisteHoy([
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => 'Advertencia',
            'modulo' => 'Produccion',
            'prioridad' => 'Alta',
            'user_id' => null,
            'role_id' => (int) $roleId,
            'estado' => 'Pendiente',
            'fecha_programada' => now(),
            'requiere_accion' => true,
            'url_accion' => route('ordenes-compra.create', ['reabastecer_insumo_id' => $insumo->id]),
            'metadata' => [
                'insumo_id' => (int) $insumo->id,
                'codigo_insumo' => (string) $insumo->codigo_insumo,
                'orden_produccion_id' => (int) $orden->id,
                'stock_disponible' => $disponible,
                'origen' => 'reserva-produccion',
            ],
        ], 'insumo_id', (int) $insumo->id);
    }
}
