<?php

namespace App\Livewire;

use App\Models\Insumo;
use App\Models\OrdenProduccion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class DashboardLogisticaMochilas extends Component
{
    public array $chartData = [];

    public function mount(): void
    {
        $this->actualizarDashboard();
    }

    public function refreshData(): void
    {
        $this->actualizarDashboard();
    }

    public function render()
    {
        return view('livewire.dashboard-logistica-mochilas');
    }

    protected function actualizarDashboard(): void
    {
        $this->chartData = [
            'produccion' => $this->buildProduccionData(),
            'pedidos' => $this->buildPedidosData(),
            'stock' => $this->buildStockData(),
        ];

        $this->dispatch('dashboard-data-updated', chartData: $this->chartData);
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array{label:string,values:array<int,float>}>}
     */
    protected function buildProduccionData(): array
    {
        $desde = now()->subDays(6)->startOfDay();
        $hasta = now()->endOfDay();

        $dias = collect();
        $cursor = $desde->copy();
        while ($cursor->lte($hasta)) {
            $dias->push($cursor->copy());
            $cursor->addDay();
        }

        $ordenes = $this->baseOrdenesQuery()
            ->whereDate('fecha_orden', '>=', $desde->toDateString())
            ->whereDate('fecha_orden', '<=', $hasta->toDateString())
            ->with('tipoProducto:id,nombre')
            ->get(['tipo_producto_id', 'fecha_orden', 'cantidad_produccion']);

        $labels = $dias->map(fn (Carbon $dia): string => $dia->format('d/m'))->values()->all();

        $datasets = $ordenes
            ->groupBy('tipo_producto_id')
            ->map(function (Collection $items) use ($dias): array {
                $nombreProducto = (string) ($items->first()?->tipoProducto?->nombre ?: 'Producto');

                $totalesPorFecha = $items
                    ->groupBy(fn ($orden): string => Carbon::parse((string) $orden->fecha_orden)->toDateString())
                    ->map(fn (Collection $rows): float => (float) $rows->sum('cantidad_produccion'));

                $values = $dias
                    ->map(fn (Carbon $dia): float => round((float) ($totalesPorFecha[$dia->toDateString()] ?? 0), 2))
                    ->values()
                    ->all();

                return [
                    'label' => $nombreProducto,
                    'values' => $values,
                ];
            })
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    protected function buildPedidosData(): array
    {
        $counts = [
            'Corte' => 0,
            'Costura' => 0,
            'Ensamblado' => 0,
            'Terminado' => 0,
        ];

        $desde = now()->subDays(6)->startOfDay();
        $hasta = now()->endOfDay();

        $ordenes = $this->baseOrdenesQuery()
            ->where('estado', '!=', OrdenProduccion::ESTADO_CANCELADA)
            ->whereDate('fecha_orden', '>=', $desde->toDateString())
            ->whereDate('fecha_orden', '<=', $hasta->toDateString())
            ->get(['estado', 'etapa_fabricacion_actual']);

        foreach ($ordenes as $orden) {
            $estado = (string) $orden->estado;

            if (OrdenProduccion::esEstadoFinalizado($estado)) {
                $counts['Terminado']++;
                continue;
            }

            $etapa = Str::lower(trim((string) $orden->etapa_fabricacion_actual));

            if (Str::contains($etapa, 'corte')) {
                $counts['Corte']++;
                continue;
            }

            if (Str::contains($etapa, 'costura')) {
                $counts['Costura']++;
                continue;
            }

            if (Str::contains($etapa, 'ensamblado')) {
                $counts['Ensamblado']++;
                continue;
            }

            // Cuando no hay etapa definida, lo tratamos como inicio de proceso (corte).
            $counts['Corte']++;
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($counts),
        ];
    }

    /**
     * @return array{labels: array<int, string>, actual: array<int, float>, minimo: array<int, float>}
     */
    protected function buildStockData(): array
    {
        $insumosObjetivo = Insumo::query()
            ->where('activo', true)
            ->orderBy('stock_actual')
            ->limit(6)
            ->get(['id', 'nombre', 'stock_actual', 'stock_minimo']);

        $labels = $insumosObjetivo
            ->map(fn (Insumo $insumo): string => (string) $insumo->nombre)
            ->values()
            ->all();

        $actual = $insumosObjetivo
            ->map(fn (Insumo $insumo): float => round((float) $insumo->stock_actual, 2))
            ->values()
            ->all();

        $minimo = $insumosObjetivo
            ->map(fn (Insumo $insumo): float => round((float) $insumo->stock_minimo, 2))
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'actual' => $actual,
            'minimo' => $minimo,
        ];
    }

    protected function baseOrdenesQuery()
    {
        return OrdenProduccion::query()
            ->where('es_plantilla_bom', false);
    }

}
