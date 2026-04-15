<?php

namespace App\Http\Controllers;

use App\Events\OrdenProduccionCreada;
use App\Http\Requests\StoreOrdenProduccionRequest;
use App\Http\Requests\UpdateOrdenProduccionRequest;
use App\Models\OrdenProduccion;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrdenProduccionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', OrdenProduccion::class);

        $query = OrdenProduccion::query()
            ->operativas()
            ->with(['tipoProducto', 'unidadMedida', 'user'])
            ->orderByDesc('fecha_orden');

        if ($request->filled('estado')) {
            $estado = (string) $request->query('estado');

            if (OrdenProduccion::esEstadoFinalizado($estado)) {
                $query->whereIn('estado', OrdenProduccion::ESTADOS_FINALIZADAS);
            } else {
                $query->where('estado', $estado);
            }
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->query('prioridad'));
        }

        if ($request->filled('tipo_producto_id')) {
            $query->where('tipo_producto_id', $request->query('tipo_producto_id'));
        }

        if ($request->filled('q')) {
            $search = (string) $request->query('q');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('numero_orden', 'like', '%' . $search . '%')
                    ->orWhere('notas', 'like', '%' . $search . '%');
            });
        }

        $ordenesProduccion = $query->paginate(15)->withQueryString();

        $tiposProducto = TipoProducto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('ordenes_produccion.index', compact('ordenesProduccion', 'tiposProducto'));
    }

    public function create(): View
    {
        $this->authorize('create', OrdenProduccion::class);

        $tiposProducto = TipoProducto::query()->where('activo', true)->orderBy('nombre')->get();
        $unidadesMedida = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();
        $usuarios = User::query()->where('activo', true)->orderBy('name')->get();

        return view('ordenes_produccion.create', compact('tiposProducto', 'unidadesMedida', 'usuarios'));
    }

    public function store(StoreOrdenProduccionRequest $request): RedirectResponse
    {
        $this->authorize('create', OrdenProduccion::class);

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['user_id'] = $data['user_id'] ?? Auth::id();

            $ordenProduccion = OrdenProduccion::create($data);

            event(new OrdenProduccionCreada($ordenProduccion));

            DB::commit();

            return redirect()
                ->route('ordenes-produccion.index')
                ->with('success', 'Orden de produccion creada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function show(OrdenProduccion $ordenProduccion): View
    {
        $this->authorize('view', $ordenProduccion);

        $ordenProduccion->load([
            'tipoProducto',
            'unidadMedida',
            'user',
            'materiales.insumo',
            'materiales.unidadMedida',
            'trazabilidadEtapas.etapaPlantilla',
            'trazabilidadEtapas.registros.user',
        ]);

        return view('ordenes_produccion.show', compact('ordenProduccion'));
    }

    public function edit(OrdenProduccion $ordenProduccion): View
    {
        $this->authorize('update', $ordenProduccion);

        $tiposProducto = TipoProducto::query()->where('activo', true)->orderBy('nombre')->get();
        $unidadesMedida = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();
        $usuarios = User::query()->where('activo', true)->orderBy('name')->get();

        return view('ordenes_produccion.edit', compact('ordenProduccion', 'tiposProducto', 'unidadesMedida', 'usuarios'));
    }

    public function update(UpdateOrdenProduccionRequest $request, OrdenProduccion $ordenProduccion): RedirectResponse
    {
        $this->authorize('update', $ordenProduccion);

        DB::beginTransaction();

        try {
            $ordenProduccion->update($request->validated());

            DB::commit();

            return redirect()
                ->route('ordenes-produccion.index')
                ->with('success', 'Orden de produccion actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function destroy(OrdenProduccion $ordenProduccion): RedirectResponse
    {
        $this->authorize('delete', $ordenProduccion);

        DB::beginTransaction();

        try {
            $ordenProduccion->delete();

            DB::commit();

            return redirect()
                ->route('ordenes-produccion.index')
                ->with('success', 'Orden de produccion eliminada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function iniciar(int $id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $ordenProduccion = OrdenProduccion::query()
                ->with('trazabilidadEtapas')
                ->findOrFail($id);

            $this->authorize('update', $ordenProduccion);

            if (! $ordenProduccion->puedeIniciar() && $ordenProduccion->estado !== 'En Pausa') {
                return back()->with('error', 'La orden no puede iniciar en su estado actual.');
            }

            $ordenProduccion->marcarEnProceso();

            $etapaPendiente = $ordenProduccion->trazabilidadEtapas()
                ->where('estado', 'Pendiente')
                ->orderBy('numero_secuencia')
                ->first();

            if ($etapaPendiente) {
                $etapaPendiente->iniciar();
            }

            DB::commit();

            return redirect()
                ->route('ordenes-produccion.show', $ordenProduccion)
                ->with('success', 'Orden de produccion iniciada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    public function completar(int $id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $ordenProduccion = OrdenProduccion::query()
                ->with('trazabilidadEtapas')
                ->findOrFail($id);

            $this->authorize('update', $ordenProduccion);

            $etapaEnProceso = $ordenProduccion->trazabilidadEtapas()
                ->where('estado', 'En Proceso')
                ->orderBy('numero_secuencia')
                ->first();

            if ($etapaEnProceso) {
                $etapaEnProceso->completar();
            }

            $etapasCompletadas = $ordenProduccion->trazabilidadEtapas()
                ->where('estado', 'Finalizada')
                ->count();

            $ordenProduccion->etapas_completadas = $etapasCompletadas;
            $ordenProduccion->calcularProgreso();

            if ($ordenProduccion->etapas_totales > 0 && $etapasCompletadas >= $ordenProduccion->etapas_totales) {
                $ordenProduccion->marcarCompletada();
            }

            DB::commit();

            return redirect()
                ->route('ordenes-produccion.show', $ordenProduccion)
                ->with('success', 'Etapa/orden de produccion completada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }
}
