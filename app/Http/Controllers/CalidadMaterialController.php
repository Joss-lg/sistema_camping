<?php

namespace App\Http\Controllers;

use App\Models\CalidadMaterialEvaluacion;
use App\Models\LoteInsumo;
use App\Models\MovimientoInventario;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalidadMaterialController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Entregas'), 403);

        $resultadoFiltro = (string) $request->query('resultado', '');

        $movimientos = MovimientoInventario::query()
            ->with(['insumo:id,nombre', 'loteInsumo:id,numero_lote,estado_calidad', 'user:id,name'])
            ->where('tipo_movimiento', MovimientoInventario::TIPO_ENTRADA)
            ->orderByDesc('fecha_movimiento')
            ->limit(250)
            ->get();

        $evaluaciones = CalidadMaterialEvaluacion::query()
            ->with('user:id,name')
            ->whereIn('movimiento_inventario_id', $movimientos->pluck('id'))
            ->orderByDesc('fecha_evaluacion')
            ->get()
            ->groupBy('movimiento_inventario_id')
            ->map(fn (Collection $items): ?CalidadMaterialEvaluacion => $items->first());

        $registros = $movimientos
            ->map(function (MovimientoInventario $movimiento) use ($evaluaciones): object {
                /** @var CalidadMaterialEvaluacion|null $evaluacion */
                $evaluacion = $evaluaciones->get($movimiento->id);

                return (object) [
                    'movimiento_id' => $movimiento->id,
                    'fecha_movimiento' => $movimiento->fecha_movimiento,
                    'insumo' => $movimiento->insumo?->nombre ?? 'Insumo',
                    'cantidad' => (float) $movimiento->cantidad,
                    'lote' => $movimiento->loteInsumo?->numero_lote,
                    'estado_lote' => (string) ($movimiento->loteInsumo?->estado_calidad ?: 'Sin estado'),
                    'registrado_por' => $movimiento->user?->name ?? 'Sistema',
                    'evaluacion' => $evaluacion,
                ];
            })
            ->when($resultadoFiltro !== '', function (Collection $items) use ($resultadoFiltro): Collection {
                return $items->filter(function (object $item) use ($resultadoFiltro): bool {
                    return (string) ($item->evaluacion?->resultado ?? '') === $resultadoFiltro;
                })->values();
            });

        $stats = (object) [
            'total_entradas' => $movimientos->count(),
            'evaluadas' => $registros->filter(fn (object $item): bool => $item->evaluacion !== null)->count(),
            'pendientes' => $registros->filter(fn (object $item): bool => $item->evaluacion === null)->count(),
        ];

        return view('calidad-material.index', [
            'registros' => $registros,
            'stats' => $stats,
            'resultadoFiltro' => $resultadoFiltro,
            'criteriosEstandar' => CalidadMaterialEvaluacion::CRITERIOS_ESTANDAR,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Entregas', 'editar'), 403);

        $data = $request->validate([
            'movimiento_inventario_id' => ['required', 'integer', 'exists:movimientos_inventario,id'],
            'resultado' => ['required', 'in:APROBADO,OBSERVADO,RECHAZADO'],
            'criterios' => ['nullable', 'array'],
            'criterios.*' => ['nullable', 'in:0,1'],
            'observaciones' => ['nullable', 'string', 'max:1500'],
            'redirect_orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $movimiento = MovimientoInventario::query()
                ->with(['insumo', 'loteInsumo'])
                ->lockForUpdate()
                ->findOrFail((int) $data['movimiento_inventario_id']);

            $criterios = collect(CalidadMaterialEvaluacion::CRITERIOS_ESTANDAR)
                ->mapWithKeys(function (string $criterio) use ($data): array {
                    $valor = (string) (($data['criterios'][$criterio] ?? '0')) === '1';

                    return [$criterio => $valor];
                })
                ->all();

            $totalCriterios = max(1, count(CalidadMaterialEvaluacion::CRITERIOS_ESTANDAR));
            $aprobados = collect($criterios)->filter()->count();
            $cumplimiento = round(($aprobados / $totalCriterios) * 100, 2);

            CalidadMaterialEvaluacion::query()->create([
                'movimiento_inventario_id' => $movimiento->id,
                'lote_insumo_id' => $movimiento->lote_insumo_id,
                'insumo_id' => $movimiento->insumo_id,
                'user_id' => (int) ($request->user()?->id ?? 0),
                'resultado' => (string) $data['resultado'],
                'criterios' => $criterios,
                'cumplimiento_porcentaje' => $cumplimiento,
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_evaluacion' => now(),
            ]);

            $movimiento->motivo = 'CALIDAD:' . (string) $data['resultado'];
            $movimiento->notas = trim((string) $movimiento->notas . "\n" . '[Evaluación de calidad] ' . (string) ($data['observaciones'] ?? 'Sin observaciones.'));
            $movimiento->save();

            if ($movimiento->lote_insumo_id) {
                $lote = LoteInsumo::query()->lockForUpdate()->find($movimiento->lote_insumo_id);

                if ($lote) {
                    $lote->estado_calidad = LoteInsumo::estadoCalidadDesdeResultado((string) $data['resultado']);
                    $lote->observaciones_calidad = trim((string) ($lote->observaciones_calidad ?? '') . "\n" . (string) ($data['observaciones'] ?? ''));
                    $lote->activo = $data['resultado'] !== CalidadMaterialEvaluacion::RESULTADO_RECHAZADO;
                    $lote->save();
                }
            }
        });

        $redirectOrdenCompraId = (int) ($data['redirect_orden_compra_id'] ?? 0);

        if ($redirectOrdenCompraId > 0) {
            return redirect()
                ->route('ordenes-compra.show', $redirectOrdenCompraId)
                ->with('ok', 'Evaluación de calidad guardada correctamente.');
        }

        return back()->with('ok', 'Evaluación de calidad guardada correctamente.');
    }
}
