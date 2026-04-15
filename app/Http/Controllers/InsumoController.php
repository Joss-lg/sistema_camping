<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsumoRequest;
use App\Http\Requests\UpdateInsumoRequest;
use App\Models\CategoriaInsumo;
use App\Models\Insumo;
use App\Models\Proveedor;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Services\StockBajoInsumosNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InsumoController extends Controller
{
	public function index(Request $request): View
	{
		$this->authorize('viewAny', Insumo::class);

		$query = Insumo::query()
			->withSum([
				'ordenesCompraDetalles as stock_entrante_confirmado' => function ($subQuery) {
					$subQuery->whereHas('ordenCompra', function ($ordenCompraQuery) {
						$ordenCompraQuery->where('estado', 'Confirmada');
					});
				},  
			], 'cantidad_solicitada')
			->with(['categoriaInsumo', 'unidadMedida', 'proveedor', 'ubicacionAlmacen'])
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
		$this->sincronizarNotificacionesStockBajo($insumos->getCollection(), 'controller.insumos.index');
		$categorias = CategoriaInsumo::query()->orderBy('nombre')->get();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();

		return view('insumos.index', compact('insumos', 'categorias', 'proveedores'));
	}

	public function create(): View
	{
		$this->authorize('create', Insumo::class);

		$categorias = CategoriaInsumo::query()->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$ubicaciones = UbicacionAlmacen::query()->where('activo', true)->orderBy('codigo_ubicacion')->get();

		return view('insumos.create', compact('categorias', 'unidades', 'proveedores', 'ubicaciones'));
	}

	public function store(StoreInsumoRequest $request): RedirectResponse
	{
		$this->authorize('create', Insumo::class);

		DB::beginTransaction();

		try {
			$payload = $this->normalizarPayload($request->validated());
			Insumo::create($payload);

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
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$ubicaciones = UbicacionAlmacen::query()->where('activo', true)->orderBy('codigo_ubicacion')->get();

		return view('insumos.edit', compact('insumo', 'categorias', 'unidades', 'proveedores', 'ubicaciones'));
	}

	public function update(UpdateInsumoRequest $request, Insumo $insumo): RedirectResponse
	{
		$this->authorize('update', $insumo);

		DB::beginTransaction();

		try {
			$payload = $this->normalizarPayload($request->validated());
			$insumo->update($payload);

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

		$this->sincronizarNotificacionesStockBajo($insumos->getCollection(), 'controller.insumos.bajo_stock');

		return view('insumos.bajo_stock', compact('insumos'));
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function normalizarPayload(array $data): array
	{
		$activo = filter_var($data['activo'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
		$data['activo'] = $activo ?? (bool) ($data['activo'] ?? true);

		$estado = trim((string) ($data['estado'] ?? ''));

		if (! $data['activo']) {
			$data['estado'] = 'Inactivo';
		} elseif ($estado === '' || strcasecmp($estado, 'Inactivo') === 0) {
			$data['estado'] = 'Activo';
		}

		return $data;
	}

	/**
	 * @param Collection<int, Insumo> $insumos
	 */
	private function sincronizarNotificacionesStockBajo(Collection $insumos, string $origen): void
	{
		if ($insumos->isEmpty()) {
			return;
		}

		/** @var StockBajoInsumosNotifier $notifier */
		$notifier = app(StockBajoInsumosNotifier::class);

		foreach ($insumos as $insumo) {
			if (! $insumo instanceof Insumo) {
				continue;
			}

			if (! $insumo->activo || (float) $insumo->stock_actual > (float) $insumo->stock_minimo) {
				continue;
			}

			$notifier->notificar($insumo, $origen);
		}
	}
}
