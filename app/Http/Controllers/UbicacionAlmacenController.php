<?php

namespace App\Http\Controllers;

use App\Models\UbicacionAlmacen;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UbicacionAlmacenController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function tiposCatalogo(): array
    {
        return ['Materia Prima', 'Producto Terminado', 'Mixto'];
    }

    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos'), 403);

        $q = trim((string) $request->query('q', ''));
        $tipo = trim((string) $request->query('tipo', ''));
        $estado = trim((string) $request->query('estado', ''));

        $query = UbicacionAlmacen::query()
            ->withSum('insumos as stock_insumos', 'stock_actual')
            ->withSum('inventarioProductosTerminados as stock_terminados', 'cantidad_en_almacen')
            ->orderByDesc('activo')
            ->orderBy('codigo_ubicacion');

        if ($q !== '') {
            $query->where(function ($subQuery) use ($q): void {
                $subQuery->where('codigo_ubicacion', 'like', '%' . $q . '%')
                    ->orWhere('nombre', 'like', '%' . $q . '%')
                    ->orWhere('tipo', 'like', '%' . $q . '%')
                    ->orWhere('seccion', 'like', '%' . $q . '%')
                    ->orWhere('estante', 'like', '%' . $q . '%');
            });
        }

        if ($tipo !== '') {
            $query->where('tipo', $tipo);
        }

        if ($estado === 'activo') {
            $query->where('activo', true);
        } elseif ($estado === 'inactivo') {
            $query->where('activo', false);
        }

        $ubicaciones = $query->paginate(15)->withQueryString();

        $tiposCatalogo = $this->tiposCatalogo();

        return view('almacenes.index', compact('ubicaciones', 'tiposCatalogo', 'q', 'tipo', 'estado'));
    }

    public function create(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos', 'editar'), 403);

        $tiposCatalogo = $this->tiposCatalogo();

        return view('almacenes.create', compact('tiposCatalogo'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos', 'editar'), 403);

        $tiposCatalogo = $this->tiposCatalogo();

        $data = $request->validate([
            'codigo_ubicacion' => ['required', 'string', 'max:20', 'unique:ubicaciones_almacen,codigo_ubicacion'],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in($tiposCatalogo)],
            'seccion' => ['nullable', 'string', 'max:50'],
            'estante' => ['nullable', 'string', 'max:20'],
            'nivel' => ['nullable', 'string', 'max:20'],
            'capacidad_maxima' => ['nullable', 'numeric', 'min:0'],
            'capacidad_actual' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['required', 'boolean'],
        ]);

        $this->validarCapacidades($request, $data);

        UbicacionAlmacen::query()->create([
            ...$data,
            'capacidad_actual' => (float) ($data['capacidad_actual'] ?? 0),
        ]);

        return redirect()->route('almacenes.index')->with('ok', 'Ubicacion de almacen creada correctamente.');
    }

    public function edit(Request $request, int $id): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos', 'editar'), 403);

        $ubicacion = UbicacionAlmacen::query()->findOrFail($id);
        $tiposCatalogo = $this->tiposCatalogo();

        return view('almacenes.edit', compact('ubicacion', 'tiposCatalogo'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos', 'editar'), 403);

        $ubicacion = UbicacionAlmacen::query()->findOrFail($id);
        $tiposCatalogo = $this->tiposCatalogo();

        $data = $request->validate([
            'codigo_ubicacion' => ['required', 'string', 'max:20', Rule::unique('ubicaciones_almacen', 'codigo_ubicacion')->ignore($ubicacion->id)],
            'nombre' => ['required', 'string', 'max:100'],
            'tipo' => ['required', Rule::in($tiposCatalogo)],
            'seccion' => ['nullable', 'string', 'max:50'],
            'estante' => ['nullable', 'string', 'max:20'],
            'nivel' => ['nullable', 'string', 'max:20'],
            'capacidad_maxima' => ['nullable', 'numeric', 'min:0'],
            'capacidad_actual' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['required', 'boolean'],
        ]);

        $this->validarCapacidades($request, $data);

        $ubicacion->update([
            ...$data,
            'capacidad_actual' => (float) ($data['capacidad_actual'] ?? 0),
        ]);

        return redirect()->route('almacenes.index')->with('ok', 'Ubicacion de almacen actualizada correctamente.');
    }

    public function toggleEstado(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Insumos', 'editar'), 403);

        $ubicacion = UbicacionAlmacen::query()->findOrFail($id);

        if ((bool) $ubicacion->activo) {
            $hayInsumos = $ubicacion->insumos()
                ->where(function ($query): void {
                    $query->where('activo', true)
                        ->orWhere('stock_actual', '>', 0);
                })
                ->exists();

            $hayLotesConStock = $ubicacion->lotesInsumos()
                ->where('cantidad_en_stock', '>', 0)
                ->exists();

            $hayTerminadosConStock = $ubicacion->inventarioProductosTerminados()
                ->where('cantidad_en_almacen', '>', 0)
                ->exists();

            if ($hayInsumos || $hayLotesConStock || $hayTerminadosConStock) {
                return back()->with('error', 'No se puede desactivar esta ubicacion porque tiene stock o movimientos activos asociados.');
            }
        }

        $ubicacion->activo = ! (bool) $ubicacion->activo;
        $ubicacion->save();

        return back()->with('ok', 'Estado de la ubicacion actualizado correctamente.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validarCapacidades(Request $request, array $data): void
    {
        $capacidadMaxima = isset($data['capacidad_maxima']) ? (float) $data['capacidad_maxima'] : null;
        $capacidadActual = (float) ($data['capacidad_actual'] ?? 0);

        if ($capacidadMaxima !== null && $capacidadActual > $capacidadMaxima) {
            $request->validate([
                'capacidad_actual' => ['max:' . $capacidadMaxima],
            ], [
                'capacidad_actual.max' => 'La capacidad actual no puede ser mayor que la capacidad maxima.',
            ]);
        }
    }
}
