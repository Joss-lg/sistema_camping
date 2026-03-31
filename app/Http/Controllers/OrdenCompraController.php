<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrdenCompraController extends Controller
{
	public function index(Request $request): View
	{
		$this->authorize('viewAny', OrdenCompra::class);

		$query = OrdenCompra::query()
			->with(['proveedor', 'user', 'detalles.insumo', 'detalles.unidadMedida'])
			->orderByDesc('fecha_orden');

		if ($request->filled('estado')) {
			$query->where('estado', $request->query('estado'));
		}

		if ($request->filled('proveedor_id')) {
			$query->where('proveedor_id', $request->query('proveedor_id'));
		}

		if ($request->filled('q')) {
			$search = (string) $request->query('q');
			$query->where('numero_orden', 'like', '%' . $search . '%');
		}

		$ordenesCompra = $query->paginate(15)->withQueryString();
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();

		return view('compras.index', compact('ordenesCompra', 'proveedores'));
	}

	public function create(): View
	{
		$this->authorize('create', OrdenCompra::class);

		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$insumos = Insumo::query()->with('unidadMedida')->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();

		return view('compras.create', compact('proveedores', 'insumos', 'unidades'));
	}

	public function store(StoreOrdenCompraRequest $request): RedirectResponse
	{
		$this->authorize('create', OrdenCompra::class);

		DB::beginTransaction();

		try {
			$data = $request->validated();
			$detalles = $data['detalles'];
			unset($data['detalles']);

			$data['user_id'] = Auth::id();
			$data['estado'] = $data['estado'] ?? 'Pendiente';

			$ordenCompra = OrdenCompra::create($data);

			$subtotal = 0;
			$totalCantidad = 0;

			foreach ($detalles as $index => $detalle) {
				$descuento = (float) ($detalle['descuento_porcentaje'] ?? 0);
				$base = (float) $detalle['cantidad_solicitada'] * (float) $detalle['precio_unitario'];
				$subtotalLinea = $base - ($base * ($descuento / 100));

				$ordenCompra->detalles()->create([
					'numero_linea' => $index + 1,
					'insumo_id' => $detalle['insumo_id'],
					'unidad_medida_id' => $detalle['unidad_medida_id'],
					'cantidad_solicitada' => $detalle['cantidad_solicitada'],
					'precio_unitario' => $detalle['precio_unitario'],
					'descuento_porcentaje' => $descuento,
					'subtotal' => $subtotalLinea,
					'fecha_entrega_esperada_linea' => $detalle['fecha_entrega_esperada_linea'] ?? null,
					'estado_linea' => 'Pendiente',
					'notas' => $detalle['notas'] ?? null,
				]);

				$subtotal += $subtotalLinea;
				$totalCantidad += (float) $detalle['cantidad_solicitada'];
			}

			$impuestos = (float) ($data['impuestos'] ?? 0);
			$descuentos = (float) ($data['descuentos'] ?? 0);
			$flete = (float) ($data['costo_flete'] ?? 0);

			$ordenCompra->update([
				'total_items' => count($detalles),
				'total_cantidad' => $totalCantidad,
				'subtotal' => $subtotal,
				'monto_total' => $subtotal + $impuestos - $descuentos + $flete,
			]);

			DB::commit();

			return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra creada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function show(OrdenCompra $ordenCompra): View
	{
		$this->authorize('view', $ordenCompra);

		$ordenCompra->load(['proveedor', 'user', 'detalles.insumo', 'detalles.unidadMedida', 'lotesInsumos']);

		return view('compras.show', compact('ordenCompra'));
	}

	public function edit(OrdenCompra $ordenCompra): View
	{
		$this->authorize('update', $ordenCompra);

		$ordenCompra->load(['detalles']);
		$proveedores = Proveedor::query()->orderBy('razon_social')->get();
		$insumos = Insumo::query()->with('unidadMedida')->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();

		return view('compras.edit', compact('ordenCompra', 'proveedores', 'insumos', 'unidades'));
	}

	public function update(UpdateOrdenCompraRequest $request, OrdenCompra $ordenCompra): RedirectResponse
	{
		$this->authorize('update', $ordenCompra);

		DB::beginTransaction();

		try {
			$data = $request->validated();
			$detalles = $data['detalles'] ?? null;
			unset($data['detalles']);

			$ordenCompra->update($data);

			if (is_array($detalles) && count($detalles) > 0) {
				$detallesIds = [];
				$subtotal = 0;
				$totalCantidad = 0;

				foreach ($detalles as $index => $detalle) {
					$descuento = (float) ($detalle['descuento_porcentaje'] ?? 0);
					$base = (float) $detalle['cantidad_solicitada'] * (float) $detalle['precio_unitario'];
					$subtotalLinea = $base - ($base * ($descuento / 100));

					$linea = $ordenCompra->detalles()->updateOrCreate(
						['id' => $detalle['id'] ?? 0],
						[
							'numero_linea' => $index + 1,
							'insumo_id' => $detalle['insumo_id'],
							'unidad_medida_id' => $detalle['unidad_medida_id'],
							'cantidad_solicitada' => $detalle['cantidad_solicitada'],
							'precio_unitario' => $detalle['precio_unitario'],
							'descuento_porcentaje' => $descuento,
							'subtotal' => $subtotalLinea,
							'fecha_entrega_esperada_linea' => $detalle['fecha_entrega_esperada_linea'] ?? null,
							'notas' => $detalle['notas'] ?? null,
						]
					);

					$detallesIds[] = $linea->id;
					$subtotal += $subtotalLinea;
					$totalCantidad += (float) $detalle['cantidad_solicitada'];
				}

				$ordenCompra->detalles()->whereNotIn('id', $detallesIds)->delete();

				$impuestos = (float) ($ordenCompra->impuestos ?? 0);
				$descuentos = (float) ($ordenCompra->descuentos ?? 0);
				$flete = (float) ($ordenCompra->costo_flete ?? 0);

				$ordenCompra->update([
					'total_items' => count($detalles),
					'total_cantidad' => $totalCantidad,
					'subtotal' => $subtotal,
					'monto_total' => $subtotal + $impuestos - $descuentos + $flete,
				]);
			}

			DB::commit();

			return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra actualizada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function destroy(OrdenCompra $ordenCompra): RedirectResponse
	{
		$this->authorize('delete', $ordenCompra);

		DB::beginTransaction();

		try {
			$ordenCompra->delete();

			DB::commit();

			return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra eliminada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function aprobar(OrdenCompra $ordenCompra): RedirectResponse
	{
		$this->authorize('update', $ordenCompra);

		DB::beginTransaction();

		try {
			if ($ordenCompra->estado !== 'Pendiente') {
				return back()->with('error', 'Solo se pueden aprobar ordenes en estado Pendiente.');
			}

			$ordenCompra->update(['estado' => 'Confirmada']);

			DB::commit();

			return redirect()->route('ordenes-compra.show', $ordenCompra)->with('success', 'Orden de compra aprobada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	public function recibir(Request $request, OrdenCompra $ordenCompra): RedirectResponse
	{
		$this->authorize('update', $ordenCompra);

		$request->validate([
			'ubicacion_almacen_id' => ['nullable', 'integer', 'exists:ubicaciones_almacen,id'],
			'detalles' => ['nullable', 'array'],
			'detalles.*.detalle_id' => ['required_with:detalles', 'integer', 'exists:ordenes_compra_detalles,id'],
			'detalles.*.cantidad_recibida' => ['required_with:detalles', 'numeric', 'gt:0'],
			'detalles.*.cantidad_aceptada' => ['nullable', 'numeric', 'min:0'],
			'detalles.*.estado_linea' => ['nullable', 'string', 'max:50'],
		]);

		DB::beginTransaction();

		try {
			if (! $ordenCompra->puedeRecibirse()) {
				return back()->with('error', 'La orden no puede recibirse en su estado actual.');
			}

			$ubicacionId = $request->integer('ubicacion_almacen_id')
				?: UbicacionAlmacen::query()->where('activo', true)->value('id');

			if (! $ubicacionId) {
				return back()->with('error', 'No existe una ubicacion activa para recepcion.');
			}

			$payloadDetalles = $request->input('detalles', []);

			if (count($payloadDetalles) === 0) {
				$payloadDetalles = $ordenCompra->detalles()->get()->map(function ($detalle) {
					return [
						'detalle_id' => $detalle->id,
						'cantidad_recibida' => $detalle->cantidad_solicitada,
						'cantidad_aceptada' => $detalle->cantidad_solicitada,
						'estado_linea' => 'Recibida',
					];
				})->all();
			}

			foreach ($payloadDetalles as $item) {
				$detalle = $ordenCompra->detalles()->where('id', $item['detalle_id'])->firstOrFail();

				$cantidadRecibida = (float) $item['cantidad_recibida'];
				$cantidadAceptada = (float) ($item['cantidad_aceptada'] ?? $cantidadRecibida);

				$detalle->update([
					'cantidad_recibida' => $cantidadRecibida,
					'cantidad_aceptada' => $cantidadAceptada,
					'estado_linea' => $item['estado_linea'] ?? 'Recibida',
				]);

				$insumo = $detalle->insumo;
				$saldoAnterior = (float) $insumo->stock_actual;
				$saldoPosterior = $saldoAnterior + $cantidadAceptada;

				$insumo->update([
					'stock_actual' => $saldoPosterior,
				]);

				$lote = LoteInsumo::create([
					'numero_lote' => 'LOT-' . $ordenCompra->id . '-' . $detalle->id . '-' . now()->format('YmdHis'),
					'lote_proveedor' => $ordenCompra->numero_folio_proveedor,
					'insumo_id' => $detalle->insumo_id,
					'orden_compra_id' => $ordenCompra->id,
					'proveedor_id' => $ordenCompra->proveedor_id,
					'fecha_lote' => now()->toDateString(),
					'fecha_recepcion' => now(),
					'cantidad_recibida' => $cantidadRecibida,
					'cantidad_en_stock' => $cantidadAceptada,
					'cantidad_rechazada' => max(0, $cantidadRecibida - $cantidadAceptada),
					'ubicacion_almacen_id' => $ubicacionId,
					'estado_calidad' => 'Aceptado',
					'user_recepcion_id' => Auth::id(),
					'activo' => true,
				]);

				MovimientoInventario::create([
					'tipo_movimiento' => MovimientoInventario::TIPO_ENTRADA,
					'insumo_id' => $detalle->insumo_id,
					'lote_insumo_id' => $lote->id,
					'orden_compra_id' => $ordenCompra->id,
					'cantidad' => $cantidadAceptada,
					'unidad_medida_id' => $detalle->unidad_medida_id,
					'ubicacion_destino_id' => $ubicacionId,
					'referencia_documento' => $ordenCompra->numero_orden,
					'motivo' => 'Recepcion de orden de compra',
					'user_id' => Auth::id(),
					'fecha_movimiento' => now(),
					'saldo_anterior' => $saldoAnterior,
					'saldo_posterior' => $saldoPosterior,
				]);
			}

			$ordenCompra->update([
				'estado' => 'Recibida',
				'fecha_entrega_real' => now()->toDateString(),
			]);

			DB::commit();

			return redirect()->route('ordenes-compra.show', $ordenCompra)->with('success', 'Recepcion de orden de compra completada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}
}
