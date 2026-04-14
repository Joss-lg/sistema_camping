<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Insumo;
use App\Models\InventarioProductoTerminado;
use App\Models\LoteInsumo;
use App\Models\OrdenCompra;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use App\Models\ProductoTerminado;
use App\Models\UbicacionAlmacen;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HandlesProduccionInventory
{
    /** @var array<int, Collection<int, OrdenProduccionMaterial>> */
    protected array $lineasBomCachePorProducto = [];

    protected const BOM_TEMPLATE_NOTE_FALLBACK = 'Plantilla BOM (no ejecutar)';
    protected const BOM_TEMPLATE_LEGACY_NOTE_FALLBACK = 'Orden base generada para gestión BOM.';
    protected const NOTE_ASIGNACION_DESDE_BOM = 'Asignado desde receta BOM al crear orden operativa.';
    protected const NOTE_LOTE_AJUSTE_STOCK = 'Lote de ajuste generado automáticamente para alinear stock global vs lotes.';
    protected const NOTE_ORDEN_TECNICA_AJUSTE = 'Orden técnica para ajuste automático de lotes.';

    protected function bomTemplateNote(): string
    {
        if (defined(static::class . '::BOM_TEMPLATE_NOTE')) {
            return (string) constant(static::class . '::BOM_TEMPLATE_NOTE');
        }

        return self::BOM_TEMPLATE_NOTE_FALLBACK;
    }

    /**
     * @return array<int, string>
     */
    protected function bomTemplateNotes(): array
    {
        if (defined(static::class . '::BOM_TEMPLATE_LEGACY_NOTE')) {
            return [$this->bomTemplateNote(), (string) constant(static::class . '::BOM_TEMPLATE_LEGACY_NOTE')];
        }

        return [$this->bomTemplateNote(), self::BOM_TEMPLATE_LEGACY_NOTE_FALLBACK];
    }

    protected function ocultarTerminadosDeOrdenReabierta(int $ordenId): void
    {
        $productoIds = ProductoTerminado::query()
            ->where('orden_produccion_id', $ordenId)
            ->pluck('id');

        if ($productoIds->isEmpty()) {
            return;
        }

        InventarioProductoTerminado::query()
            ->whereIn('producto_terminado_id', $productoIds)
            ->delete();

        ProductoTerminado::query()
            ->whereIn('id', $productoIds)
            ->delete();
    }

    protected function obtenerOrdenPlantillaBom(int $productoId, int $unidadId): OrdenProduccion
    {
        $orden = OrdenProduccion::query()
            ->where('tipo_producto_id', $productoId)
            ->where('es_plantilla_bom', true)
            ->orderByDesc('id')
            ->first();

        if ($orden) {
            if ((string) $orden->notas !== $this->bomTemplateNote()
                || (string) $orden->estado !== OrdenProduccion::ESTADO_CANCELADA
                || ! (bool) $orden->es_plantilla_bom) {
                $orden->update([
                    'notas' => $this->bomTemplateNote(),
                    'es_plantilla_bom' => true,
                    'estado' => OrdenProduccion::ESTADO_CANCELADA,
                    'requiere_calidad' => false,
                ]);
            }

            return $orden;
        }

        return OrdenProduccion::query()->create([
            'tipo_producto_id' => $productoId,
            'user_id' => Auth::id() ?? User::query()->value('id'),
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 1,
            'unidad_medida_id' => $unidadId,
            'estado' => OrdenProduccion::ESTADO_CANCELADA,
            'notas' => $this->bomTemplateNote(),
            'es_plantilla_bom' => true,
            'prioridad' => 'Normal',
            'requiere_calidad' => false,
            'etapas_totales' => 0,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ]);
    }

    protected function obtenerLineasBomPorProducto(int $productoId): Collection
    {
        if (isset($this->lineasBomCachePorProducto[$productoId])) {
            return $this->lineasBomCachePorProducto[$productoId];
        }

        $lineas = OrdenProduccionMaterial::query()
            ->with('insumo:id,nombre')
            ->whereHas('ordenProduccion', function ($query) use ($productoId): void {
                $query->where('tipo_producto_id', $productoId)
                    ->where('es_plantilla_bom', true);
            })
            ->orderBy('numero_linea')
            ->get();

        $this->lineasBomCachePorProducto[$productoId] = $lineas;

        return $lineas;
    }

    protected function sincronizarMaterialesDesdeBom(OrdenProduccion $orden): void
    {
        if ($orden->materiales()->exists()) {
            return;
        }

        $lineasPlantilla = $this->obtenerLineasBomPorProducto((int) $orden->tipo_producto_id)
            ->filter(fn (OrdenProduccionMaterial $linea): bool => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado')
            ->values();

        if ($lineasPlantilla->isEmpty()) {
            return;
        }

        foreach ($lineasPlantilla as $index => $lineaPlantilla) {
            OrdenProduccionMaterial::query()->create([
                'orden_produccion_id' => $orden->id,
                'insumo_id' => (int) $lineaPlantilla->insumo_id,
                'unidad_medida_id' => (int) $lineaPlantilla->unidad_medida_id,
                'cantidad_necesaria' => round((float) $lineaPlantilla->cantidad_necesaria, 4),
                'cantidad_utilizada' => 0,
                'cantidad_desperdicio' => 0,
                'estado_asignacion' => 'Asignado',
                'notas_asignacion' => self::NOTE_ASIGNACION_DESDE_BOM,
                'numero_linea' => $index + 1,
            ]);
        }
    }

    protected function obtenerLoteDisponibleParaConsumo(int $insumoId): ?LoteInsumo
    {
        $baseQuery = LoteInsumo::query()
            ->where('insumo_id', $insumoId)
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', LoteInsumo::ESTADO_CALIDAD_RECHAZADO);
            });

        $lote = (clone $baseQuery)
            ->where('activo', true)
            ->orderBy('fecha_recepcion')
            ->orderBy('id')
            ->first();

        if ($lote) {
            return $lote;
        }

        return (clone $baseQuery)
            ->orderByDesc('activo')
            ->orderBy('fecha_recepcion')
            ->orderBy('id')
            ->first();
    }

    /**
     * @param Collection<int, Insumo> $insumos
     */
    protected function regularizarDesfaseLotes(Collection $insumos): void
    {
        if ($insumos->isEmpty()) {
            return;
        }

        $insumos = $insumos->filter(fn ($insumo): bool => $insumo instanceof Insumo)->values();

        if ($insumos->isEmpty()) {
            return;
        }

        $insumoIds = $insumos->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $stockPorLotes = LoteInsumo::query()
            ->selectRaw('insumo_id, SUM(cantidad_en_stock) as stock_lotes')
            ->whereIn('insumo_id', $insumoIds)
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', LoteInsumo::ESTADO_CALIDAD_RECHAZADO);
            })
            ->groupBy('insumo_id')
            ->pluck('stock_lotes', 'insumo_id');

        $ubicacionActivaId = UbicacionAlmacen::query()->where('activo', true)->value('id');

        foreach ($insumos as $insumo) {
            $stockGlobal = round((float) $insumo->stock_actual, 4);
            $stockLotes = round((float) ($stockPorLotes[$insumo->id] ?? 0), 4);
            $desfase = round($stockGlobal - $stockLotes, 4);

            if ($desfase <= 0.0001) {
                continue;
            }

            $ordenCompraAjuste = $this->resolverOrdenCompraAjuste($insumo);

            if (! $ordenCompraAjuste) {
                continue;
            }

            $ubicacionId = $insumo->ubicacion_almacen_id ?: $ubicacionActivaId;

            if (! $ubicacionId) {
                continue;
            }

            LoteInsumo::query()->create([
                'numero_lote' => sprintf('AJ-%d-%s', (int) $insumo->id, now()->format('YmdHisu')),
                'insumo_id' => (int) $insumo->id,
                'orden_compra_id' => (int) $ordenCompraAjuste->id,
                'proveedor_id' => (int) $ordenCompraAjuste->proveedor_id,
                'fecha_lote' => now()->toDateString(),
                'fecha_recepcion' => now(),
                'cantidad_recibida' => $desfase,
                'cantidad_en_stock' => $desfase,
                'cantidad_consumida' => 0,
                'cantidad_rechazada' => 0,
                'ubicacion_almacen_id' => $ubicacionId,
                'estado_calidad' => LoteInsumo::ESTADO_CALIDAD_ACEPTADO,
                'user_recepcion_id' => Auth::id(),
                'notas' => self::NOTE_LOTE_AJUSTE_STOCK,
                'activo' => true,
            ]);
        }
    }

    protected function resolverOrdenCompraAjuste(Insumo $insumo): ?OrdenCompra
    {
        $ordenCompraDesdeLote = LoteInsumo::query()
            ->where('insumo_id', (int) $insumo->id)
            ->whereNotNull('orden_compra_id')
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeLote) {
            $orden = OrdenCompra::query()->find((int) $ordenCompraDesdeLote);
            if ($orden) {
                return $orden;
            }
        }

        $ordenCompraDesdeDetalle = DB::table('ordenes_compra_detalles')
            ->where('insumo_id', (int) $insumo->id)
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeDetalle) {
            $orden = OrdenCompra::query()->find((int) $ordenCompraDesdeDetalle);
            if ($orden) {
                return $orden;
            }
        }

        $proveedorId = (int) ($insumo->proveedor_id ?: 0);

        if ($proveedorId <= 0) {
            $proveedorId = (int) (DB::table('proveedores')->orderBy('id')->value('id') ?: 0);
        }

        if ($proveedorId <= 0) {
            return null;
        }

        $notaTecnica = self::NOTE_ORDEN_TECNICA_AJUSTE;
        $ordenTecnica = OrdenCompra::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'Recibida')
            ->where('notas', $notaTecnica)
            ->first();

        if ($ordenTecnica) {
            return $ordenTecnica;
        }

        $userId = (int) (Auth::id() ?: User::query()->value('id'));

        if ($userId <= 0) {
            return null;
        }

        return OrdenCompra::query()->create([
            'proveedor_id' => $proveedorId,
            'user_id' => $userId,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Recibida',
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
            'notas' => $notaTecnica,
        ]);
    }
}
