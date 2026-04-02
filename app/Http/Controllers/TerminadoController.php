<?php

namespace App\Http\Controllers;

use App\Events\OrdenProduccionCompletada;
use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\UbicacionAlmacen;
use App\Services\IdentificacionProductoService;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TerminadoController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Terminados'), 403);

        // Limpieza defensiva: evita mostrar terminados de órdenes reabiertas.
        $this->ocultarTerminadosDeOrdenesNoFinalizadas();

        // Backfill defensivo: si existen órdenes cerradas que no alcanzaron a generar inventario,
        // se sincronizan al entrar al módulo sin duplicar registros existentes.
        $this->sincronizarOrdenesFinalizadasSinInventario();

        $ubicacionFiltro = $request->integer('ubicacion_almacen_id');

        $inventario = InventarioProductoTerminado::query()
            ->with([
                'tipoProducto:id,nombre,slug,stock_minimo_terminado',
                'unidadMedida:id,nombre',
                'ubicacionAlmacen:id,nombre,codigo_ubicacion',
                'productoTerminado:id,numero_lote_produccion,numero_serie,codigo_barras,codigo_qr,estado,estado_calidad',
            ])
            ->when($ubicacionFiltro > 0, fn ($query) => $query->where('ubicacion_almacen_id', $ubicacionFiltro))
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        $productos = $inventario->map(function (InventarioProductoTerminado $item): object {
            $stock = (float) $item->cantidad_en_almacen;
            $stockMinimo = (float) ($item->tipoProducto?->stock_minimo_terminado ?? 5);
            $stockMaximo = max($stock * 1.5, 1.0);

            return (object) [
                'id' => $item->id,
                'nombre' => $item->tipoProducto?->nombre ?? 'Producto terminado',
                'sku' => $item->tipoProducto?->slug ?? ('TERM-' . $item->id),
                'stock' => $stock,
                'stock_minimo' => $stockMinimo,
                'stock_maximo' => $stockMaximo,
                'categoria' => (object) ['nombre' => 'Terminados'],
                'unidad' => (object) ['nombre' => $item->unidadMedida?->nombre ?? 'Unidad'],
                'ubicacion' => (object) [
                    'nombre' => $item->ubicacionAlmacen?->nombre,
                    'codigo' => $item->ubicacionAlmacen?->codigo_ubicacion,
                ],
                'numero_lote_produccion' => $item->productoTerminado?->numero_lote_produccion,
                'numero_serie' => $item->productoTerminado?->numero_serie,
                'codigo_barras' => $item->productoTerminado?->codigo_barras,
                'codigo_qr' => $item->productoTerminado?->codigo_qr,
                'estado_inventario' => $item->estado,
                'estado_producto' => $item->productoTerminado?->estado,
                'estado_calidad' => $item->productoTerminado?->estado_calidad,
            ];
        });

        $statsTotalProductos = $productos->count();
        $statsStockBajo = $productos->filter(fn (object $p): bool => $p->stock <= $p->stock_minimo)->count();
        $statsLotes = $inventario->count();

        $ordenesFinalizadas = $this->obtenerOrdenesFinalizadas();
        $ubicaciones = UbicacionAlmacen::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo_ubicacion']);

        return view('terminados.index', compact(
            'statsTotalProductos',
            'statsStockBajo',
            'statsLotes',
            'ordenesFinalizadas',
            'productos',
            'ubicaciones',
            'ubicacionFiltro'
        ));
    }

    public function storeIngreso(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Terminados', 'editar'), 403);

        $data = $request->validate([
            'orden_produccion_id' => ['required', 'integer', 'exists:ordenes_produccion,id'],
            'cantidad_ingreso' => ['required', 'numeric', 'gt:0'],
            'ubicacion_almacen_id' => ['nullable', 'integer', 'exists:ubicaciones_almacen,id'],
        ]);

        $orden = OrdenProduccion::query()
            ->with(['tipoProducto:id,nombre', 'unidadMedida:id'])
            ->findOrFail((int) $data['orden_produccion_id']);

        $ubicacion = UbicacionAlmacen::query()
            ->when(
                ! empty($data['ubicacion_almacen_id']),
                fn ($query) => $query->where('id', (int) $data['ubicacion_almacen_id']),
                fn ($query) => $query->where('activo', true)->orderBy('id')
            )
            ->first();

        if (! $ubicacion) {
            return back()->withErrors(['orden_produccion_id' => 'No existe una ubicacion de almacen activa para registrar ingresos.']);
        }

        if (! $orden->tipo_producto_id || ! $orden->unidad_medida_id || ! $orden->user_id) {
            return back()->withErrors(['orden_produccion_id' => 'La orden seleccionada no tiene datos minimos para generar producto terminado.']);
        }

        DB::transaction(function () use ($orden, $ubicacion, $data): void {
            $cantidadIngreso = (float) $data['cantidad_ingreso'];
            $lote = $this->generarLoteUnico($orden->id);
            $numeroSerie = IdentificacionProductoService::generarNumeroSerie((int) $orden->id, (int) $orden->tipo_producto_id);
            $codigoBarras = IdentificacionProductoService::generarCodigoBarras((int) $orden->id, (int) $orden->tipo_producto_id);
            $codigoQr = IdentificacionProductoService::generarCodigoQr($lote, $numeroSerie);

            $productoTerminado = ProductoTerminado::query()->create([
                'numero_lote_produccion' => $lote,
                'numero_serie' => $numeroSerie,
                'orden_produccion_id' => $orden->id,
                'tipo_producto_id' => $orden->tipo_producto_id,
                'user_responsable_id' => $orden->user_id,
                'fecha_produccion' => now(),
                'fecha_finalizacion' => now(),
                'cantidad_producida' => $cantidadIngreso,
                'unidad_medida_id' => $orden->unidad_medida_id,
                'estado' => ProductoTerminado::ESTADO_PRODUCIDO,
                'estado_calidad' => ProductoTerminado::ESTADO_CALIDAD_PENDIENTE,
                'costo_produccion' => (float) ($orden->costo_real ?? $orden->costo_estimado ?? 0),
                'codigo_barras' => $codigoBarras,
                'codigo_qr' => $codigoQr,
                'notas' => sprintf(
                    'Ingreso registrado desde modulo de terminados. SKU %s',
                    IdentificacionProductoService::generarSkuVisual($orden)
                ),
            ]);

            $precioUnitario = 0.0;
            if ($cantidadIngreso > 0 && (float) ($orden->costo_real ?? 0) > 0) {
                $precioUnitario = round(((float) $orden->costo_real) / $cantidadIngreso, 4);
            }

            InventarioProductoTerminado::query()->create([
                'producto_terminado_id' => $productoTerminado->id,
                'tipo_producto_id' => $orden->tipo_producto_id,
                'ubicacion_almacen_id' => $ubicacion->id,
                'cantidad_en_almacen' => $cantidadIngreso,
                'unidad_medida_id' => $orden->unidad_medida_id,
                'cantidad_reservada' => 0,
                'fecha_ingreso_almacen' => now()->toDateString(),
                'estado' => InventarioProductoTerminado::ESTADO_PENDIENTE_INSPECCION,
                'precio_unitario' => $precioUnitario,
                'valor_total_inventario' => round($cantidadIngreso * $precioUnitario, 4),
                'notas' => sprintf(
                    'Ingreso inicial generado desde orden #%d. Lote %s. Pendiente de aprobación de calidad.',
                    $orden->id,
                    $lote
                ),
                'requiere_inspeccion_periodica' => true,
            ]);
        });

        return redirect()->route('terminados.index')->with('ok', 'Ingreso de producto terminado registrado correctamente.');
    }

    public function revisionCalidad(Request $request, ProductoTerminado $productoTerminado): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Terminados', 'editar'), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:APROBADO,RECHAZADO'],
            'observaciones_calidad' => ['nullable', 'string', 'max:1000'],
        ]);

        $inspectorId = (int) ($request->user()?->id ?? 0);
        $observaciones = trim((string) ($data['observaciones_calidad'] ?? ''));

        DB::transaction(function () use ($productoTerminado, $data, $inspectorId, $observaciones): void {
            $producto = ProductoTerminado::query()
                ->lockForUpdate()
                ->findOrFail($productoTerminado->id);

            $inventarios = InventarioProductoTerminado::query()
                ->where('producto_terminado_id', $producto->id)
                ->lockForUpdate()
                ->get();

            if ($data['decision'] === 'APROBADO') {
                $producto->marcarAprobadoPor($inspectorId > 0 ? $inspectorId : null);

                if ($observaciones !== '') {
                    $producto->observaciones_calidad = $observaciones;
                    $producto->save();
                }

                foreach ($inventarios as $inventario) {
                    $inventario->estado = InventarioProductoTerminado::ESTADO_EN_ALMACEN;
                    $inventario->notas = trim((string) $inventario->notas . "\n[Aprobado calidad] " . now()->format('Y-m-d H:i'));
                    $inventario->requiere_inspeccion_periodica = false;
                    $inventario->save();
                }

                return;
            }

            $producto->marcarRechazadoPor($inspectorId > 0 ? $inspectorId : null, $observaciones);

            foreach ($inventarios as $inventario) {
                $inventario->estado = InventarioProductoTerminado::ESTADO_DESCARTADO;
                $inventario->notas = trim((string) $inventario->notas . "\n[Rechazado calidad] " . ($observaciones !== '' ? $observaciones : now()->format('Y-m-d H:i')));
                $inventario->requiere_inspeccion_periodica = false;
                $inventario->save();
            }
        });

        return back()->with('ok', $data['decision'] === 'APROBADO'
            ? 'Producto aprobado por calidad y habilitado en almacén.'
            : 'Producto rechazado por calidad y bloqueado para salida.');
    }

    public function storeAjuste(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Terminados', 'editar'), 403);

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:inventario_productos_terminados,id'],
            'tipo_ajuste' => ['required', 'in:SUMAR,RESTAR'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $inventario = InventarioProductoTerminado::query()->findOrFail((int) $data['producto_id']);
        $cantidadActual = (float) $inventario->cantidad_en_almacen;
        $cantidadAjuste = (float) $data['cantidad'];

        if ($data['tipo_ajuste'] === 'RESTAR' && $cantidadAjuste > $cantidadActual) {
            return back()->withErrors(['cantidad' => 'No se puede restar una cantidad mayor al stock actual.']);
        }

        $nuevoStock = $data['tipo_ajuste'] === 'SUMAR'
            ? $cantidadActual + $cantidadAjuste
            : $cantidadActual - $cantidadAjuste;

        $notaAjuste = sprintf(
            "[%s] Ajuste %s de %.4f. Motivo: %s",
            now()->format('Y-m-d H:i'),
            $data['tipo_ajuste'],
            $cantidadAjuste,
            trim((string) $data['motivo'])
        );

        $inventario->cantidad_en_almacen = $nuevoStock;
        $inventario->estado = $nuevoStock > 0
            ? (string) $inventario->estado
            : InventarioProductoTerminado::ESTADO_DESCARTADO;
        $inventario->notas = trim((string) $inventario->notas . "\n" . $notaAjuste);
        $inventario->save();
        $inventario->actualizarValor();

        return redirect()->route('terminados.index')->with('ok', 'Ajuste de inventario aplicado correctamente.');
    }

    /**
     * @return Collection<int, object>
     */
    private function obtenerOrdenesFinalizadas(): Collection
    {
        return OrdenProduccion::query()
            ->with(['tipoProducto:id,nombre'])
            ->whereIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->map(function (OrdenProduccion $orden): object {
                $cantidadCompletada = (float) ($orden->cantidad_produccion ?? 0);

                return (object) [
                    'id' => $orden->id,
                    'cantidad_completada' => $cantidadCompletada,
                    'cantidad_ingresada' => 0.0,
                    'producto' => (object) [
                        'nombre' => $orden->tipoProducto?->nombre ?? 'Producto',
                    ],
                ];
            });
    }

    private function generarLoteUnico(int $ordenId): string
    {
        do {
            $codigo = sprintf('LOTE-%s-%04d', now()->format('Ymd'), random_int(1, 9999));
            if ($ordenId > 0) {
                $codigo = sprintf('OP%s-%s', $ordenId, $codigo);
            }
        } while (ProductoTerminado::query()->where('numero_lote_produccion', $codigo)->exists());

        return $codigo;
    }

    private function sincronizarOrdenesFinalizadasSinInventario(): void
    {
        $ordenesPendientes = OrdenProduccion::query()
            ->whereIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS)
            ->where(function ($query): void {
                $query->doesntHave('productosTerminados')
                    ->orWhereHas('productosTerminados', function ($subQuery): void {
                        $subQuery->doesntHave('inventario');
                    });
            })
            ->limit(100)
            ->get();

        foreach ($ordenesPendientes as $orden) {
            event(new OrdenProduccionCompletada($orden));
        }
    }

    private function ocultarTerminadosDeOrdenesNoFinalizadas(): void
    {
        $productoIds = ProductoTerminado::query()
            ->whereHas('ordenProduccion', function ($query): void {
                $query->whereNotIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS);
            })
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
}
