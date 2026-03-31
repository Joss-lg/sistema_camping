<?php

namespace App\Listeners;

use App\Events\OrdenProduccionCompletada;
use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\UbicacionAlmacen;
use App\Services\IdentificacionProductoService;
use Illuminate\Support\Facades\DB;

class CrearInventarioProductoTerminado
{
    public function handle(OrdenProduccionCompletada $event): void
    {
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

            $productoTerminado = ProductoTerminado::query()->firstOrCreate(
                [
                    'orden_produccion_id' => $orden->id,
                    'tipo_producto_id' => $orden->tipo_producto_id,
                ],
                [
                    'numero_lote_produccion' => $lote,
                    'numero_serie' => $numeroSerie,
                    'user_responsable_id' => $orden->user_id,
                    'fecha_produccion' => $orden->fecha_inicio_real ?? now(),
                    'fecha_finalizacion' => $orden->fecha_fin_real ?? now(),
                    'cantidad_producida' => $orden->cantidad_produccion,
                    'unidad_medida_id' => $orden->unidad_medida_id,
                    'estado' => 'Producido',
                    'estado_calidad' => 'Pendiente Inspección',
                    'costo_produccion' => $orden->costo_real ?? 0,
                    'codigo_barras' => $codigoBarras,
                    'codigo_qr' => $codigoQr,
                    'notas' => 'Registro generado automaticamente al completar la orden ' . $orden->numero_orden,
                ]
            );

            $precioUnitario = 0;
            if ((float) $orden->cantidad_produccion > 0 && (float) $orden->costo_real > 0) {
                $precioUnitario = (float) $orden->costo_real / (float) $orden->cantidad_produccion;
            }

            InventarioProductoTerminado::query()->firstOrCreate(
                [
                    'producto_terminado_id' => $productoTerminado->id,
                    'ubicacion_almacen_id' => $ubicacionId,
                ],
                [
                    'tipo_producto_id' => $orden->tipo_producto_id,
                    'cantidad_en_almacen' => $productoTerminado->cantidad_producida,
                    'unidad_medida_id' => $orden->unidad_medida_id,
                    'cantidad_reservada' => 0,
                    'fecha_ingreso_almacen' => now()->toDateString(),
                    'estado' => 'En Almacén',
                    'precio_unitario' => $precioUnitario,
                    'valor_total_inventario' => (float) $productoTerminado->cantidad_producida * $precioUnitario,
                    'notas' => 'Ingreso automatico por cierre de orden ' . $orden->numero_orden,
                    'requiere_inspeccion_periodica' => false,
                ]
            );
        });
    }
}
