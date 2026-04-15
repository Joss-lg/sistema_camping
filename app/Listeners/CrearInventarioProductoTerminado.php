<?php

namespace App\Listeners;

use App\Events\OrdenProduccionCompletada;
use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\UbicacionAlmacen;
use App\Services\IdentificacionProductoService;
use App\Services\NotificacionSistemaPatternService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrearInventarioProductoTerminado
{
    public function handle(OrdenProduccionCompletada $event): void
    {
        try {
            DB::transaction(function () use ($event): void {
                $orden = OrdenProduccion::query()
                    ->lockForUpdate()
                    ->find($event->ordenProduccion->id);

                if (! $orden || ! OrdenProduccion::esEstadoFinalizado((string) $orden->estado)) {
                    return;
                }

                $ubicacionId = UbicacionAlmacen::query()
                    ->where('activo', true)
                    ->orderBy('id')
                    ->value('id')
                    ?? UbicacionAlmacen::query()->orderBy('id')->value('id');

                if (! $ubicacionId) {
                    return;
                }

                $lote = sprintf('LOTE-OP-%06d', $orden->id);
                $numeroSerie = IdentificacionProductoService::generarNumeroSerie((int) $orden->id, (int) $orden->tipo_producto_id);
                $codigoBarras = IdentificacionProductoService::generarCodigoBarras((int) $orden->id, (int) $orden->tipo_producto_id);
                $codigoQr = IdentificacionProductoService::generarCodigoQr($lote, $numeroSerie);

                $productoTerminado = ProductoTerminado::query()
                    ->withTrashed()
                    ->where('orden_produccion_id', $orden->id)
                    ->where('tipo_producto_id', $orden->tipo_producto_id)
                    ->first();

                if (! $productoTerminado) {
                    $productoTerminado = ProductoTerminado::query()
                        ->withTrashed()
                        ->where('numero_lote_produccion', $lote)
                        ->first();
                }

                if ($productoTerminado) {
                    $productoTerminado->fill([
                        'orden_produccion_id' => $orden->id,
                        'tipo_producto_id' => $orden->tipo_producto_id,
                        'numero_lote_produccion' => $lote,
                        'numero_serie' => $numeroSerie,
                        'user_responsable_id' => $orden->user_id,
                        'fecha_produccion' => $orden->fecha_inicio_real ?? now(),
                        'fecha_finalizacion' => $orden->fecha_fin_real ?? now(),
                        'cantidad_producida' => $orden->cantidad_produccion,
                        'unidad_medida_id' => $orden->unidad_medida_id,
                        'estado' => ProductoTerminado::ESTADO_PRODUCIDO,
                        'estado_calidad' => ProductoTerminado::ESTADO_CALIDAD_PENDIENTE,
                        'costo_produccion' => $orden->costo_real ?? 0,
                        'codigo_barras' => $codigoBarras,
                        'codigo_qr' => $codigoQr,
                        'notas' => 'Registro generado automaticamente al completar la orden ' . $orden->numero_orden,
                    ]);

                    if (method_exists($productoTerminado, 'trashed') && $productoTerminado->trashed()) {
                        $productoTerminado->restore();
                    }

                    $productoTerminado->save();
                } else {
                    $productoTerminado = ProductoTerminado::query()->create([
                        'orden_produccion_id' => $orden->id,
                        'tipo_producto_id' => $orden->tipo_producto_id,
                        'numero_lote_produccion' => $lote,
                        'numero_serie' => $numeroSerie,
                        'user_responsable_id' => $orden->user_id,
                        'fecha_produccion' => $orden->fecha_inicio_real ?? now(),
                        'fecha_finalizacion' => $orden->fecha_fin_real ?? now(),
                        'cantidad_producida' => $orden->cantidad_produccion,
                        'unidad_medida_id' => $orden->unidad_medida_id,
                        'estado' => ProductoTerminado::ESTADO_PRODUCIDO,
                        'estado_calidad' => ProductoTerminado::ESTADO_CALIDAD_PENDIENTE,
                        'costo_produccion' => $orden->costo_real ?? 0,
                        'codigo_barras' => $codigoBarras,
                        'codigo_qr' => $codigoQr,
                        'notas' => 'Registro generado automaticamente al completar la orden ' . $orden->numero_orden,
                    ]);
                }

                $precioUnitario = 0;
                if ((float) $orden->cantidad_produccion > 0 && (float) $orden->costo_real > 0) {
                    $precioUnitario = (float) $orden->costo_real / (float) $orden->cantidad_produccion;
                }

                $inventario = InventarioProductoTerminado::query()
                    ->withTrashed()
                    ->where('producto_terminado_id', $productoTerminado->id)
                    ->where('ubicacion_almacen_id', $ubicacionId)
                    ->first();

                if ($inventario) {
                    $inventario->fill([
                        'tipo_producto_id' => $orden->tipo_producto_id,
                        'cantidad_en_almacen' => $productoTerminado->cantidad_producida,
                        'unidad_medida_id' => $orden->unidad_medida_id,
                        'cantidad_reservada' => 0,
                        'fecha_ingreso_almacen' => now()->toDateString(),
                        'estado' => InventarioProductoTerminado::ESTADO_PENDIENTE_INSPECCION,
                        'precio_unitario' => $precioUnitario,
                        'valor_total_inventario' => (float) $productoTerminado->cantidad_producida * $precioUnitario,
                        'notas' => 'Ingreso automatico por cierre de orden ' . $orden->numero_orden . '. Pendiente de aprobación de calidad.',
                        'requiere_inspeccion_periodica' => true,
                    ]);

                    if (method_exists($inventario, 'trashed') && $inventario->trashed()) {
                        $inventario->restore();
                    }

                    $inventario->save();
                } else {
                    InventarioProductoTerminado::query()->create([
                        'producto_terminado_id' => $productoTerminado->id,
                        'ubicacion_almacen_id' => $ubicacionId,
                        'tipo_producto_id' => $orden->tipo_producto_id,
                        'cantidad_en_almacen' => $productoTerminado->cantidad_producida,
                        'unidad_medida_id' => $orden->unidad_medida_id,
                        'cantidad_reservada' => 0,
                        'fecha_ingreso_almacen' => now()->toDateString(),
                        'estado' => InventarioProductoTerminado::ESTADO_PENDIENTE_INSPECCION,
                        'precio_unitario' => $precioUnitario,
                        'valor_total_inventario' => (float) $productoTerminado->cantidad_producida * $precioUnitario,
                        'notas' => 'Ingreso automatico por cierre de orden ' . $orden->numero_orden . '. Pendiente de aprobación de calidad.',
                        'requiere_inspeccion_periodica' => true,
                    ]);
                }

                $notificacionService = app(NotificacionSistemaPatternService::class);
                $destinatarios = $notificacionService->usuariosActivos();

                foreach ($destinatarios as $usuario) {
                    $notificacionService->crearSiNoExisteHoy([
                        'titulo' => 'Orden finalizada y enviada a inspección',
                        'mensaje' => sprintf(
                            'La orden %s finalizó producción y su producto quedó pendiente de inspección de calidad.',
                            (string) ($orden->numero_orden ?: ('#' . $orden->id))
                        ),
                        'tipo' => 'Alerta',
                        'modulo' => 'Terminados',
                        'prioridad' => 'Alta',
                        'user_id' => (int) $usuario->id,
                        'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                        'estado' => 'Pendiente',
                        'fecha_programada' => now(),
                        'requiere_accion' => true,
                        'url_accion' => '/terminados',
                        'metadata' => [
                            'tipo_alerta' => 'orden_finalizada_pendiente_inspeccion',
                            'orden_produccion_id' => (int) $orden->id,
                            'producto_terminado_id' => (int) $productoTerminado->id,
                            'origen' => 'listener.crear_inventario_producto_terminado',
                        ],
                    ], 'orden_produccion_id', (int) $orden->id);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Listener CrearInventarioProductoTerminado fallo', [
                'listener' => self::class,
                'evento' => OrdenProduccionCompletada::class,
                'orden_produccion_id' => (int) ($event->ordenProduccion->id ?? 0),
                'mensaje' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
