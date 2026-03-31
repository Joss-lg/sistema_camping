<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsumoRequest;
use App\Http\Requests\UpdateInsumoRequest;
use App\Models\CategoriaInsumo;
use App\Models\Insumo;
use App\Models\Proveedor;
use App\Models\TipoProducto;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InsumoController extends Controller
{
	public function index(Request $request): View
	{
		$this->authorize('viewAny', Insumo::class);

		$query = Insumo::query()
			->with(['categoriaInsumo', 'unidadMedida', 'tipoProducto', 'proveedor', 'ubicacionAlmacen'])
			->orderBy('nombre');

		if ($request->filled('q')) {
			$search = (string) $request->query('q');
			$query->where(function ($subQuery) use ($search) {
				$subQuery->where('codigo_insumo', 'like', '%' . $search . '%')
					->orWhere('nombre', 'like', '%' . $search . '%');
			});
		}

		if ($request->filled('categoria_insumo_id')) {
			$query->where('categoria_insumo_id', $request->query('categoria_insumo_id'));
		}

		if ($request->filled('proveedor_id')) {
			$query->where('proveedor_id', $request->query('proveedor_id'));
		}

		if ($request->filled('estado')) {
			$query->where('estado', $request->query('estado'));
		}

		$insumos = $query->paginate(15)->withQueryString();
		$categorias = CategoriaInsumo::query()->orderBy('nombre')->get();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();

		return view('insumos.index', compact('insumos', 'categorias', 'proveedores'));
	}

	public function create(): View
	{
		$this->authorize('create', Insumo::class);

		$categorias = CategoriaInsumo::query()->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();
		$tiposProducto = TipoProducto::query()->where('activo', true)->orderBy('nombre')->get();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$ubicaciones = UbicacionAlmacen::query()->where('activo', true)->orderBy('codigo_ubicacion')->get();

		return view('insumos.create', compact('categorias', 'unidades', 'tiposProducto', 'proveedores', 'ubicaciones'));
	}

	public function store(StoreInsumoRequest $request): RedirectResponse
	{
		$this->authorize('create', Insumo::class);

		DB::beginTransaction();

		try {
			Insumo::create($request->validated());

			DB::commit();

			return redirect()->route('insumos.index')->with('success', 'Insumo creado correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function show(Insumo $insumo): View
	{
		$this->authorize('view', $insumo);

		$insumo->load([
			'categoriaInsumo',
			'unidadMedida',
			'tipoProducto',
			'proveedor',
			'ubicacionAlmacen',
			'lotesInsumos',
			'movimientosInventario',
		]);

		return view('insumos.show', compact('insumo'));
	}

	public function edit(Insumo $insumo): View
	{
		$this->authorize('update', $insumo);

		$categorias = CategoriaInsumo::query()->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();
		$tiposProducto = TipoProducto::query()->where('activo', true)->orderBy('nombre')->get();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$ubicaciones = UbicacionAlmacen::query()->where('activo', true)->orderBy('codigo_ubicacion')->get();

		return view('insumos.edit', compact('insumo', 'categorias', 'unidades', 'tiposProducto', 'proveedores', 'ubicaciones'));
	}

	public function update(UpdateInsumoRequest $request, Insumo $insumo): RedirectResponse
	{
		$this->authorize('update', $insumo);

		DB::beginTransaction();

		try {
			$insumo->update($request->validated());

			DB::commit();

			return redirect()->route('insumos.index')->with('success', 'Insumo actualizado correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function destroy(Insumo $insumo): RedirectResponse
	{
		$this->authorize('delete', $insumo);

		DB::beginTransaction();

		try {
			$insumo->delete();

			DB::commit();

			return redirect()->route('insumos.index')->with('success', 'Insumo eliminado correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function bajoStock(): View
	{
		$this->authorize('viewAny', Insumo::class);

		$insumos = Insumo::query()
			->with(['categoriaInsumo', 'unidadMedida', 'proveedor'])
			->bajoStock()
			->orderByRaw('(stock_actual - stock_minimo) asc')
			->paginate(15);

		return view('insumos.bajo_stock', compact('insumos'));
	}
}
