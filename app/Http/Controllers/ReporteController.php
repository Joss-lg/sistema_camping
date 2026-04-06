<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\ReporteGenerado;
use App\Models\TipoProducto;
use App\Services\PermisoService;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Reportes'), 403);

        [$from, $to] = $this->resolverRango($request);

        $entregas = $this->obtenerEntregasPorRango($from, $to)
            ->take(200)
            ->values();

        $ordenesProduccion = OrdenProduccion::query()
            ->with('tipoProducto:id,nombre,slug')
            ->whereBetween('fecha_orden', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('fecha_orden')
            ->limit(200)
            ->get()
            ->map(function (OrdenProduccion $orden): object {
                $cantidadObjetivo = (float) $orden->cantidad_produccion;
                $cantidadCompletada = $cantidadObjetivo * ((float) $orden->porcentaje_completado / 100);

                return (object) [
                    'id' => $orden->id,
                    'created_at' => $orden->fecha_orden,
                    'producto' => (object) [
                        'nombre' => $orden->tipoProducto?->nombre,
                        'sku' => strtoupper((string) ($orden->tipoProducto?->slug ?: 'N/A')),
                    ],
                    'estado' => (object) [
                        'nombre' => (string) $orden->estado,
                    ],
                    'cantidad' => $cantidadObjetivo,
                    'cantidad_completada' => $cantidadCompletada,
                ];
            });

        $productos = TipoProducto::query()
            ->with('inventarioProductosTerminados.unidadMedida:id,nombre')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function (TipoProducto $tipo): object {
                $stock = (float) $tipo->inventarioProductosTerminados->sum('cantidad_en_almacen');
                $stockMinimo = (float) ($tipo->stock_minimo_terminado ?: 0);
                $stockMaximo = $stockMinimo > 0 ? $stockMinimo * 3 : max($stock, 1);
                $unidad = $tipo->inventarioProductosTerminados->first()?->unidadMedida?->nombre;

                return (object) [
                    'nombre' => $tipo->nombre,
                    'sku' => strtoupper((string) ($tipo->slug ?: 'N/A')),
                    'categoria' => (object) ['nombre' => 'CAMPING'],
                    'unidad' => (object) ['nombre' => $unidad ?: '-'],
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                    'stock_maximo' => $stockMaximo,
                ];
            });

        $insumosBajo = $this->obtenerInsumosBajoConEntrante(100)
            ->map(fn (Insumo $insumo): object => (object) [
                'nombre' => $insumo->nombre,
                'categoria' => (object) ['nombre' => $insumo->categoriaInsumo?->nombre],
                'unidad' => (object) ['nombre' => $insumo->unidadMedida?->nombre],
                'stock' => (float) $insumo->stock_actual,
                'stock_minimo' => (float) $insumo->stock_minimo,
                'stock_entrante_confirmado' => (float) ($insumo->stock_entrante_confirmado ?? 0),
                'stock_proyectado' => (float) $insumo->stock_actual + (float) ($insumo->stock_entrante_confirmado ?? 0),
            ]);

        $statsEntregas = $entregas->count();
        $statsCantidadEntregada = (float) $entregas->sum('cantidad_entregada');
        $statsOrdenesProduccion = $ordenesProduccion->count();
        $statsCantidadCompletada = (float) $ordenesProduccion->sum('cantidad_completada');
        $statsLotes = ProductoTerminado::query()
            ->whereBetween('fecha_produccion', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->count();
        $statsInsumosBajo = $insumosBajo->count();

        return view('reportes.index', compact(
            'from',
            'to',
            'statsEntregas',
            'statsCantidadEntregada',
            'statsOrdenesProduccion',
            'statsCantidadCompletada',
            'statsLotes',
            'statsInsumosBajo',
            'entregas',
            'ordenesProduccion',
            'productos',
            'insumosBajo'
        ));
    }

    public function exportCsv(Request $request): Response
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Reportes'), 403);

        $type = (string) $request->query('type', 'reporte');
        [$from, $to] = $this->resolverRango($request);

        $filename = sprintf('%s_%s_%s.csv', $type, str_replace('-', '', $from), str_replace('-', '', $to));
        [$encabezados, $filas] = $this->resolverDatasetExportacion($type, $from, $to);

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $encabezados);
        foreach ($filas as $fila) {
            fputcsv($stream, $fila);
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        $diasExpiracion = $this->obtenerDiasExpiracionReportes();
        ReporteGenerado::query()->create([
            'codigo_reporte' => strtoupper('REP-' . now()->format('YmdHis') . '-' . Str::random(6)),
            'nombre_reporte' => $filename,
            'tipo_reporte' => $type,
            'formato' => 'csv',
            'parametros' => ['from' => $from, 'to' => $to, 'type' => $type],
            'ruta_archivo' => null,
            'generado_por_user_id' => $request->user()?->id,
            'fecha_desde' => $from,
            'fecha_hasta' => $to,
            'total_registros' => count($filas),
            'tamano_bytes' => strlen($csv),
            'estado' => 'Descargado',
            'expiracion_at' => now()->addDays($diasExpiracion),
            'notas' => 'Exportacion CSV descargada desde modulo de reportes.',
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolverRango(Request $request): array
    {
        $hoy = now();
        $fromDefault = $hoy->copy()->subDay()->toDateString();
        $toDefault = $hoy->toDateString();

        $from = (string) $request->query('from', $fromDefault);
        $to = (string) $request->query('to', $toDefault);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = $fromDefault;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $toDefault;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array{0:array<int,string>,1:array<int,array<int,string|int|float|null>>}
     */
    private function resolverDatasetExportacion(string $type, string $from, string $to): array
    {
        if ($type === 'entregas') {
            $rows = $this->obtenerEntregasPorRango($from, $to)
                ->map(fn (object $entrega): array => [
                $entrega->id,
                optional($entrega->fecha_entrega)->format('Y-m-d H:i:s'),
                $entrega->proveedor?->nombre,
                $entrega->material?->nombre,
                (float) $entrega->cantidad_entregada,
                (string) $entrega->estado_calidad,
            ])->all();

            return [['id', 'fecha_entrega', 'proveedor', 'material', 'cantidad', 'calidad'], $rows];
        }

        if ($type === 'produccion') {
            $data = OrdenProduccion::query()
                ->with('tipoProducto:id,nombre,slug')
                ->whereBetween('fecha_orden', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orderByDesc('fecha_orden')
                ->get();

            $rows = $data->map(function (OrdenProduccion $orden): array {
                $cantidadObjetivo = (float) $orden->cantidad_produccion;
                $cantidadCompletada = $cantidadObjetivo * ((float) $orden->porcentaje_completado / 100);

                return [
                    $orden->id,
                    (string) ($orden->tipoProducto?->nombre ?: '-'),
                    strtoupper((string) ($orden->tipoProducto?->slug ?: 'N/A')),
                    (string) $orden->estado,
                    $cantidadObjetivo,
                    $cantidadCompletada,
                    optional($orden->fecha_orden)->format('Y-m-d H:i:s'),
                ];
            })->all();

            return [['orden_id', 'producto', 'sku', 'estado', 'cantidad_objetivo', 'cantidad_completada', 'fecha_orden'], $rows];
        }

        if ($type === 'insumos-bajo') {
            $data = $this->obtenerInsumosBajoConEntrante();

            $rows = $data->map(fn (Insumo $insumo): array => [
                $insumo->id,
                $insumo->nombre,
                $insumo->categoriaInsumo?->nombre,
                (float) $insumo->stock_actual,
                (float) ($insumo->stock_entrante_confirmado ?? 0),
                (float) $insumo->stock_actual + (float) ($insumo->stock_entrante_confirmado ?? 0),
                (float) $insumo->stock_minimo,
                $insumo->unidadMedida?->nombre,
            ])->all();

            return [['insumo_id', 'insumo', 'categoria', 'stock_actual', 'stock_entrante_confirmado', 'stock_proyectado', 'stock_minimo', 'unidad'], $rows];
        }

        $rows = ProductoTerminado::query()
            ->whereBetween('fecha_produccion', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('fecha_produccion')
            ->get()
            ->map(fn (ProductoTerminado $producto): array => [
                $producto->id,
                $producto->numero_lote_produccion,
                $producto->numero_serie,
                (string) $producto->estado,
                optional($producto->fecha_produccion)->format('Y-m-d H:i:s'),
            ])
            ->all();

        return [['producto_id', 'lote', 'serie', 'estado', 'fecha_produccion'], $rows];
    }

    private function obtenerDiasExpiracionReportes(): int
    {
        $config = ConfiguracionSistema::query()->firstOrCreate(
            ['clave' => 'reportes.dias_expiracion'],
            [
                'valor' => '30',
                'tipo_dato' => 'integer',
                'categoria' => 'reportes',
                'descripcion' => 'Dias de vigencia para registros de reportes generados.',
                'es_publica' => false,
                'editable' => true,
                'orden_visualizacion' => 10,
                'activo' => true,
            ]
        );

        return max(1, (int) ($config->valor_tipado ?? 30));
    }

    /**
     * @return Collection<int, object>
     */
    private function obtenerEntregasPorRango(string $from, string $to): Collection
    {
        $entregasDesdeOrdenes = OrdenCompra::query()
            ->with([
                'proveedor:id,nombre_comercial,razon_social',
                'detalles.insumo:id,nombre',
            ])
            ->whereNotNull('fecha_entrega_real')
            ->whereDate('fecha_entrega_real', '>=', $from)
            ->whereDate('fecha_entrega_real', '<=', $to)
            ->where(function ($query): void {
                $query->whereRaw('LOWER(TRIM(estado)) = ?', ['recibida'])
                    ->orWhereHas('detalles', function ($detalleQuery): void {
                        $detalleQuery
                            ->where('cantidad_recibida', '>', 0)
                            ->orWhere('cantidad_aceptada', '>', 0);
                    });
            })
            ->orderByDesc('fecha_entrega_real')
            ->get()
            ->flatMap(function (OrdenCompra $orden): Collection {
                $ordenMarcadaComoRecibida = strcasecmp(trim((string) $orden->estado), 'Recibida') === 0;

                return $orden->detalles
                    ->filter(function ($detalle) use ($ordenMarcadaComoRecibida): bool {
                        return $ordenMarcadaComoRecibida
                            || (float) $detalle->cantidad_recibida > 0
                            || (float) $detalle->cantidad_aceptada > 0;
                    })
                    ->map(function ($detalle) use ($orden, $ordenMarcadaComoRecibida): object {
                        [$estadoRevision] = $this->parseRevision((string) ($detalle->notas_recepcion ?: $detalle->estado_linea));

                        $cantidadEntregada = (float) ($detalle->cantidad_aceptada ?: $detalle->cantidad_recibida ?: 0);

                        if ($cantidadEntregada <= 0 && $ordenMarcadaComoRecibida) {
                            $cantidadEntregada = (float) $detalle->cantidad_solicitada;
                        }

                        $estadoCalidad = (string) ($detalle->estado_linea ?: '');
                        if ($estadoCalidad === '' || ($ordenMarcadaComoRecibida && $estadoCalidad === 'Pendiente')) {
                            $estadoCalidad = 'RECIBIDA';
                        }

                        return (object) [
                            'id' => 'OC-' . $orden->id . '-' . $detalle->id,
                            'fecha_entrega' => $orden->fecha_entrega_real,
                            'proveedor' => (object) [
                                'nombre' => $orden->proveedor?->nombre_comercial ?: $orden->proveedor?->razon_social,
                            ],
                            'material' => (object) [
                                'nombre' => $detalle->insumo?->nombre,
                            ],
                            'cantidad_entregada' => $cantidadEntregada,
                            'estado_calidad' => $estadoCalidad,
                            'estado_revision' => $estadoRevision,
                        ];
                    });
            });

        $entregasFallback = MovimientoInventario::query()
            ->with([
                'insumo.proveedor:id,nombre_comercial,razon_social',
                'ordenCompra:id,fecha_entrega_real',
            ])
            ->where('tipo_movimiento', MovimientoInventario::TIPO_ENTRADA)
            ->where(function ($query): void {
                $query->whereNull('orden_compra_id')
                    ->orWhereHas('ordenCompra', function ($ordenQuery): void {
                        $ordenQuery->whereNull('fecha_entrega_real');
                    });
            })
            ->whereBetween('fecha_movimiento', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('fecha_movimiento')
            ->get()
            ->map(function (MovimientoInventario $mov): object {
                [$estadoRevision] = $this->parseRevision((string) $mov->motivo);

                return (object) [
                    'id' => $mov->id,
                    'fecha_entrega' => $mov->fecha_movimiento,
                    'proveedor' => (object) [
                        'nombre' => $mov->insumo?->proveedor?->nombre_comercial ?: $mov->insumo?->proveedor?->razon_social,
                    ],
                    'material' => (object) [
                        'nombre' => $mov->insumo?->nombre,
                    ],
                    'cantidad_entregada' => (float) $mov->cantidad,
                    'estado_calidad' => $mov->motivo ?: 'ACEPTADO',
                    'estado_revision' => $estadoRevision,
                ];
            });

        return $entregasDesdeOrdenes
            ->concat($entregasFallback)
            ->sortByDesc(fn (object $entrega) => optional($entrega->fecha_entrega)?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * @return Collection<int, Insumo>
     */
    private function obtenerInsumosBajoConEntrante(?int $limit = null): Collection
    {
        $insumos = Insumo::query()
            ->with(['categoriaInsumo:id,nombre', 'unidadMedida:id,nombre'])
            ->withSum([
                'ordenesCompraDetalles as stock_entrante_confirmado' => function ($subQuery): void {
                    $subQuery->whereHas('ordenCompra', function ($ordenCompraQuery): void {
                        $ordenCompraQuery->where('estado', 'Confirmada');
                    });
                },
            ], 'cantidad_solicitada')
            ->orderBy('id')
            ->get();

        $bajoStock = $insumos
            ->filter(function (Insumo $insumo): bool {
                $stockProyectado = (float) $insumo->stock_actual + (float) ($insumo->stock_entrante_confirmado ?? 0);

                return $stockProyectado <= (float) $insumo->stock_minimo;
            })
            ->sortBy(function (Insumo $insumo): float {
                $stockProyectado = (float) $insumo->stock_actual + (float) ($insumo->stock_entrante_confirmado ?? 0);

                return $stockProyectado - (float) $insumo->stock_minimo;
            })
            ->values();

        if (is_int($limit) && $limit > 0) {
            return $bajoStock->take($limit)->values();
        }

        return $bajoStock;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parseRevision(string $motivo): array
    {
        if (str_starts_with($motivo, 'REVISION:')) {
            return [str_replace('REVISION:', '', $motivo), ''];
        }

        return ['PENDIENTE', ''];
    }
}
