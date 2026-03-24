<?php

namespace App\Http\Controllers;

use App\Models\EntregaProveedor;
use App\Models\Material;
use App\Models\OrdenProduccion;
use App\Models\ProductoLote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $productos = \App\Models\ProductoTerminado::with(['categoria:id,nombre', 'unidad:id,nombre'])
            ->orderBy('nombre')
            ->get();
        [$from, $to, $fromRaw, $toRaw] = $this->resolveRange($request);

        $entregas = EntregaProveedor::with(['proveedor:id,nombre', 'material:id,nombre'])
            ->whereDate('fecha_entrega', '>=', $fromRaw)
            ->whereDate('fecha_entrega', '<=', $toRaw)
            ->orderByDesc('fecha_entrega')
            ->get();

        $ordenesProduccion = OrdenProduccion::with(['producto:id,nombre,sku', 'estado:id,nombre'])
            ->whereDate('created_at', '>=', $fromRaw)
            ->whereDate('created_at', '<=', $toRaw)
            ->orderByDesc('id')
            ->get();

        $lotes = ProductoLote::with(['producto:id,nombre,sku', 'estado:id,nombre'])
            ->whereDate('fecha_produccion', '>=', $fromRaw)
            ->whereDate('fecha_produccion', '<=', $toRaw)
            ->orderByDesc('fecha_produccion')
            ->get();

        $insumosBajo = Material::with(['unidad:id,nombre', 'categoria:id,nombre'])
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('nombre')
            ->get();

        return view('reportes.index', [
            'from' => $fromRaw,
            'to' => $toRaw,
            'entregas' => $entregas,
            'ordenesProduccion' => $ordenesProduccion,
            'lotes' => $lotes,
            'insumosBajo' => $insumosBajo,
            'productos' => $productos,
            'statsEntregas' => (int) $entregas->count(),
            'statsCantidadEntregada' => (float) $entregas->sum('cantidad_entregada'),
            'statsOrdenesProduccion' => (int) $ordenesProduccion->count(),
            'statsCantidadCompletada' => (float) $ordenesProduccion->sum('cantidad_completada'),
            'statsLotes' => (int) $lotes->count(),
            'statsInsumosBajo' => (int) $insumosBajo->count(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to, $fromRaw, $toRaw] = $this->resolveRange($request);
        $type = (string) $request->query('type', 'entregas');

        $filename = 'reporte_'.$type.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($type, $fromRaw, $toRaw): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            if ($type === 'produccion') {
                fputcsv($handle, ['orden_id', 'producto', 'sku', 'estado', 'cantidad_objetivo', 'cantidad_completada', 'cantidad_ingresada', 'fecha_registro']);
                OrdenProduccion::with(['producto:id,nombre,sku', 'estado:id,nombre'])
                    ->whereDate('created_at', '>=', $fromRaw)
                    ->whereDate('created_at', '<=', $toRaw)
                    ->orderByDesc('id')
                    ->chunk(200, function ($rows) use ($handle): void {
                        foreach ($rows as $orden) {
                            fputcsv($handle, [
                                $orden->id,
                                $orden->producto?->nombre,
                                $orden->producto?->sku,
                                $orden->estado?->nombre,
                                $orden->cantidad,
                                $orden->cantidad_completada,
                                $orden->cantidad_ingresada,
                                optional($orden->created_at)->format('Y-m-d H:i:s'),
                            ]);
                        }
                    });
            } elseif ($type === 'lotes') {
                fputcsv($handle, ['lote_id', 'numero_lote', 'producto', 'sku', 'estado', 'fecha_produccion']);
                ProductoLote::with(['producto:id,nombre,sku', 'estado:id,nombre'])
                    ->whereDate('fecha_produccion', '>=', $fromRaw)
                    ->whereDate('fecha_produccion', '<=', $toRaw)
                    ->orderByDesc('id')
                    ->chunk(200, function ($rows) use ($handle): void {
                        foreach ($rows as $lote) {
                            fputcsv($handle, [
                                $lote->id,
                                $lote->numero_lote,
                                $lote->producto?->nombre,
                                $lote->producto?->sku,
                                $lote->estado?->nombre,
                                optional($lote->fecha_produccion)->format('Y-m-d H:i:s'),
                            ]);
                        }
                    });
            } elseif ($type === 'insumos-bajo') {
                fputcsv($handle, ['material_id', 'material', 'categoria', 'unidad', 'stock', 'stock_minimo']);
                Material::with(['categoria:id,nombre', 'unidad:id,nombre'])
                    ->whereColumn('stock', '<=', 'stock_minimo')
                    ->orderBy('nombre')
                    ->chunk(200, function ($rows) use ($handle): void {
                        foreach ($rows as $material) {
                            fputcsv($handle, [
                                $material->id,
                                $material->nombre,
                                $material->categoria?->nombre,
                                $material->unidad?->nombre,
                                $material->stock,
                                $material->stock_minimo,
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, ['entrega_id', 'fecha_entrega', 'proveedor', 'material', 'cantidad', 'estado_calidad', 'estado_revision']);
                EntregaProveedor::with(['proveedor:id,nombre', 'material:id,nombre'])
                    ->whereDate('fecha_entrega', '>=', $fromRaw)
                    ->whereDate('fecha_entrega', '<=', $toRaw)
                    ->orderByDesc('fecha_entrega')
                    ->chunk(200, function ($rows) use ($handle): void {
                        foreach ($rows as $entrega) {
                            fputcsv($handle, [
                                $entrega->id,
                                optional($entrega->fecha_entrega)->format('Y-m-d H:i:s'),
                                $entrega->proveedor?->nombre,
                                $entrega->material?->nombre,
                                $entrega->cantidad_entregada,
                                $entrega->estado_calidad,
                                $entrega->estado_revision,
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $fromRaw = (string) $request->query('from', now()->subDays(30)->format('Y-m-d'));
        $toRaw = (string) $request->query('to', now()->format('Y-m-d'));

        // Convertir las fechas en strings YYYY-MM-DD a Carbon
        // Usar parsing estricto para evitar problemas de timezone
        $from = Carbon::createFromFormat('Y-m-d', $fromRaw)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toRaw)->endOfDay();

        // Si "to" es anterior a "from", intercambiarlas
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            [$fromRaw, $toRaw] = [$from->toDateString(), $to->toDateString()];
        }

        return [$from, $to, $fromRaw, $toRaw];
    }
}
