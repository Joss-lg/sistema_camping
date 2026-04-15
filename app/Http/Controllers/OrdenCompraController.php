<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;
use App\Models\CalidadMaterialEvaluacion;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Services\NotificacionSistemaPatternService;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class OrdenCompraController extends Controller
{
	private const NOTE_PREFILL_REABASTECIMIENTO = 'Reabastecimiento por stock bajo desde módulo de Insumos.';

	public function index(Request $request): View
	{
		$this->authorize('viewAny', OrdenCompra::class);
		$user = Auth::user();
		$isProveedor = $this->isProveedorRole($user);
		$proveedorIds = $isProveedor && $user ? $this->resolveProveedorIdsForUser($user) : [];

		$query = OrdenCompra::query()
			->with(['proveedor', 'user', 'detalles.insumo', 'detalles.unidadMedida'])
			->when($isProveedor, function ($builder) use ($proveedorIds): void {
				$builder->whereIn('proveedor_id', $proveedorIds ?: [0]);
			})
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
		$proveedores = Proveedor::query()
			->when($isProveedor, function ($builder) use ($proveedorIds): void {
				$builder->whereIn('id', $proveedorIds ?: [0]);
			})
			->orderBy('razon_social')
			->get();

		return view('compras.index', compact('ordenesCompra', 'proveedores'));
	}

	private function isProveedorRole($user): bool
	{
		$roleKey = PermisoService::normalizeRoleKey((string) ($user?->role?->slug ?: $user?->role?->nombre ?: ''));

		return $roleKey === 'PROVEEDOR';
	}

	/**
	 * @return array<int>
	 */
	private function resolveProveedorIdsForUser($user): array
	{
		$proveedorId = (int) ($user?->proveedor_id ?? 0);

		if ($proveedorId > 0) {
			return [$proveedorId];
		}

		$email = $user?->email;

		if (! $email) {
			return [];
		}

		return DB::table('proveedores')
			->select('proveedores.id')
			->leftJoin('contactos_proveedores', 'contactos_proveedores.proveedor_id', '=', 'proveedores.id')
			->where(function ($query) use ($email): void {
				$query->where('proveedores.email_general', $email)
					->orWhere('contactos_proveedores.email', $email);
			})
			->distinct()
			->pluck('proveedores.id')
			->map(fn ($id): int => (int) $id)
			->all();
	}

	public function create(Request $request): View
	{
		$this->authorize('create', OrdenCompra::class);

		$proveedores = Proveedor::query()
			->with(['contactos' => function ($query): void {
				$query->select([
					'id',
					'proveedor_id',
					'nombre_completo',
					'cargo',
					'telefono',
					'telefono_movil',
					'email',
					'es_contacto_principal',
				]);
			}])
			->orderBy('razon_social')
			->get();
		$insumos = Insumo::query()->with('unidadMedida')->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();

		$prefillDetalle = null;
		$prefillProveedorId = old('proveedor_id');

		$reabastecerInsumoId = $request->integer('reabastecer_insumo_id');
		if ($reabastecerInsumoId > 0) {
			$insumoPrefill = $insumos->firstWhere('id', $reabastecerInsumoId);

			if ($insumoPrefill) {
				$cantidadSugeridaRequest = $request->query('cantidad_sugerida');
				$cantidadSugerida = is_numeric($cantidadSugeridaRequest)
					? (float) $cantidadSugeridaRequest
					: max(
						(float) ($insumoPrefill->cantidad_minima_orden ?? 0),
						max(0, (float) $insumoPrefill->stock_minimo - (float) $insumoPrefill->stock_actual)
					);

				$prefillDetalle = [
					'insumo_id' => (int) $insumoPrefill->id,
					'unidad_medida_id' => (int) ($insumoPrefill->unidad_medida_id ?? 0),
					'cantidad_solicitada' => max(1, $cantidadSugerida),
					'precio_unitario' => (float) ($insumoPrefill->precio_costo ?? 0),
					'descuento_porcentaje' => 0,
					'fecha_entrega_esperada_linea' => now()->addDays(3)->toDateString(),
					'notas' => self::NOTE_PREFILL_REABASTECIMIENTO,
				];

				$prefillProveedorId = old('proveedor_id', $insumoPrefill->proveedor_id);
			}
		}

		return view('compras.create', compact('proveedores', 'insumos', 'unidades', 'prefillDetalle', 'prefillProveedorId'));
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

		$movimientos = MovimientoInventario::query()
			->with(['insumo:id,nombre', 'loteInsumo:id,numero_lote,estado_calidad', 'user:id,name'])
			->where('tipo_movimiento', MovimientoInventario::TIPO_ENTRADA)
			->where('orden_compra_id', $ordenCompra->id)
			->orderByDesc('fecha_movimiento')
			->limit(250)
			->get();

		$evaluaciones = CalidadMaterialEvaluacion::query()
			->with('user:id,name')
			->whereIn('movimiento_inventario_id', $movimientos->pluck('id'))
			->orderByDesc('fecha_evaluacion')
			->get()
			->groupBy('movimiento_inventario_id')
			->map(fn ($items): ?CalidadMaterialEvaluacion => $items->first());

		$registrosCalidad = $movimientos
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
			->values();

		$statsCalidad = (object) [
			'total_entradas' => $movimientos->count(),
			'evaluadas' => $registrosCalidad->filter(fn (object $item): bool => $item->evaluacion !== null)->count(),
			'pendientes' => $registrosCalidad->filter(fn (object $item): bool => $item->evaluacion === null)->count(),
		];

		$canEvaluarCalidad = PermisoService::canAccessModule(Auth::user(), 'Entregas', 'editar');

		return view('compras.show', [
			'ordenCompra' => $ordenCompra,
			'registrosCalidad' => $registrosCalidad,
			'statsCalidad' => $statsCalidad,
			'criteriosEstandar' => CalidadMaterialEvaluacion::CRITERIOS_ESTANDAR,
			'canEvaluarCalidad' => $canEvaluarCalidad,
		]);
	}

	public function pdf(OrdenCompra $ordenCompra): Response
	{
		$this->authorize('view', $ordenCompra);

		$ordenCompra->load([
			'proveedor.contactos',
			'user',
			'detalles.insumo',
			'detalles.unidadMedida',
		]);

		$contactoProveedor = $ordenCompra->proveedor?->contactos
			?->firstWhere('es_contacto_principal', true)
			?: $ordenCompra->proveedor?->contactos?->first();

		$pdf = Pdf::loadView('compras.pdf_orden', [
			'ordenCompra' => $ordenCompra,
			'contactoProveedor' => $contactoProveedor,
			'fechaGeneracion' => now()->format('d/m/Y H:i'),
		]);

		$nombreArchivo = sprintf('orden-compra-%s.pdf', $ordenCompra->numero_orden ?: $ordenCompra->id);

		return $pdf->download($nombreArchivo);
	}

	public function edit(OrdenCompra $ordenCompra): View
	{
		$this->authorize('update', $ordenCompra);

		$ordenCompra->load(['detalles']);
		$proveedores = Proveedor::query()
			->with(['contactos' => function ($query): void {
				$query->select([
					'id',
					'proveedor_id',
					'nombre_completo',
					'cargo',
					'telefono',
					'telefono_movil',
					'email',
					'es_contacto_principal',
				]);
			}])
			->orderBy('razon_social')
			->get();
		$insumos = Insumo::query()->with('unidadMedida')->orderBy('nombre')->get();
		$unidades = UnidadMedida::query()->where('activo', true)->orderBy('nombre')->get();

		return view('compras.edit', compact('ordenCompra', 'proveedores', 'insumos', 'unidades'));
	}

	public function update(UpdateOrdenCompraRequest $request, OrdenCompra $ordenCompra): RedirectResponse
	{
		$this->authorize('update', $ordenCompra);

		DB::beginTransaction();

		try {
			$estadoAnterior = (string) $ordenCompra->estado;
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

			$estadoNuevo = (string) ($data['estado'] ?? $ordenCompra->estado);
			$cambioARecibida = strcasecmp(trim($estadoAnterior), 'Recibida') !== 0
				&& strcasecmp(trim($estadoNuevo), 'Recibida') === 0;

			if ($cambioARecibida) {
				$fechaRecepcion = isset($data['fecha_entrega_real']) && is_string($data['fecha_entrega_real'])
					? $data['fecha_entrega_real']
					: null;

				$this->procesarRecepcionOrden($ordenCompra, null, [], $fechaRecepcion);
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
			$this->procesarRecepcionOrden(
				$ordenCompra,
				$request->integer('ubicacion_almacen_id'),
				$request->input('detalles', []),
				now()->toDateString()
			);

			DB::commit();

			return redirect()->route('ordenes-compra.show', $ordenCompra)->with('success', 'Recepcion de orden de compra completada correctamente.');
		} catch (\Exception $e) {
			DB::rollBack();

			return back()->withInput()->with('error', 'Error al procesar: ' . $e->getMessage());
		}
	}

	/**
	 * @param  array<int, array<string, mixed>>  $payloadDetalles
	 */
	private function procesarRecepcionOrden(OrdenCompra $ordenCompra, ?int $ubicacionId = null, array $payloadDetalles = [], ?string $fechaEntrega = null): void
	{
		if (! $ordenCompra->puedeRecibirse() && strcasecmp(trim((string) $ordenCompra->estado), 'Recibida') !== 0) {
			throw new \RuntimeException('La orden no puede recibirse en su estado actual.');
		}

		$ubicacionFinal = $ubicacionId ?: UbicacionAlmacen::query()->where('activo', true)->value('id');

		if (! $ubicacionFinal) {
			throw new \RuntimeException('No existe una ubicacion activa para recepcion.');
		}

		if (count($payloadDetalles) === 0) {
			$payloadDetalles = $ordenCompra->detalles()->get()->map(function ($detalle): array {
				return [
					'detalle_id' => $detalle->id,
					'cantidad_recibida' => $detalle->cantidad_solicitada,
					'cantidad_aceptada' => $detalle->cantidad_solicitada,
					'estado_linea' => 'Recibida',
				];
			})->all();
		}

		$fechaMovimiento = $fechaEntrega ?: now()->toDateString();
		$notificacionService = app(NotificacionSistemaPatternService::class);
		$destinatarios = $notificacionService->usuariosActivos();

		foreach ($payloadDetalles as $item) {
			$detalleId = isset($item['detalle_id']) ? (int) $item['detalle_id'] : 0;
			$detalle = $ordenCompra->detalles()->where('id', $detalleId)->firstOrFail();

			$cantidadRecibida = (float) ($item['cantidad_recibida'] ?? 0);
			$cantidadAceptada = (float) ($item['cantidad_aceptada'] ?? $cantidadRecibida);

			if ($cantidadRecibida <= 0) {
				continue;
			}

			$detalle->update([
				'cantidad_recibida' => $cantidadRecibida,
				'cantidad_aceptada' => $cantidadAceptada,
				'estado_linea' => (string) ($item['estado_linea'] ?? 'Recibida'),
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
				'fecha_lote' => $fechaMovimiento,
				'fecha_recepcion' => now(),
				'cantidad_recibida' => $cantidadRecibida,
				'cantidad_en_stock' => $cantidadAceptada,
				'cantidad_rechazada' => max(0, $cantidadRecibida - $cantidadAceptada),
				'ubicacion_almacen_id' => $ubicacionFinal,
				'estado_calidad' => LoteInsumo::ESTADO_CALIDAD_ACEPTADO,
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
				'ubicacion_destino_id' => $ubicacionFinal,
				'referencia_documento' => $ordenCompra->numero_orden,
				'motivo' => 'Recepcion de orden de compra',
				'user_id' => Auth::id(),
				'fecha_movimiento' => $fechaMovimiento,
				'saldo_anterior' => $saldoAnterior,
				'saldo_posterior' => $saldoPosterior,
			]);

			foreach ($destinatarios as $usuario) {
				$notificacionService->crearSiNoExisteHoy([
					'titulo' => 'Nuevo stock registrado',
					'mensaje' => sprintf(
						'Se recibio stock del insumo %s (%s) por OC %s. Cantidad aceptada: %.2f. Stock actual: %.2f.',
						(string) $insumo->nombre,
						(string) $insumo->codigo_insumo,
						(string) $ordenCompra->numero_orden,
						$cantidadAceptada,
						$saldoPosterior
					),
					'tipo' => 'Informativa',
					'modulo' => 'Compras',
					'prioridad' => 'Media',
					'user_id' => (int) $usuario->id,
					'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
					'estado' => 'Pendiente',
					'fecha_programada' => now(),
					'requiere_accion' => false,
					'url_accion' => '/insumos',
					'metadata' => [
						'tipo_alerta' => 'nuevo_stock_oc',
						'alerta_insumo' => 'nuevo_stock_oc:' . (int) $insumo->id,
						'orden_compra_id' => (int) $ordenCompra->id,
						'insumo_id' => (int) $insumo->id,
						'origen' => 'ordenes_compra.recibir',
					],
				], 'alerta_insumo', 'nuevo_stock_oc:' . (int) $insumo->id);

				if ($saldoPosterior <= (float) $insumo->stock_minimo) {
					$notificacionService->crearSiNoExisteHoy([
						'titulo' => 'Reabastecimiento insuficiente',
						'mensaje' => sprintf(
							'El insumo %s (%s) sigue bajo minimo despues de recibir OC %s. Stock actual: %.2f, minimo: %.2f.',
							(string) $insumo->nombre,
							(string) $insumo->codigo_insumo,
							(string) $ordenCompra->numero_orden,
							$saldoPosterior,
							(float) $insumo->stock_minimo
						),
						'tipo' => 'Alerta',
						'modulo' => 'Compras',
						'prioridad' => 'Alta',
						'user_id' => (int) $usuario->id,
						'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
						'estado' => 'Pendiente',
						'fecha_programada' => now(),
						'requiere_accion' => true,
						'url_accion' => '/ordenes-compra/create',
						'metadata' => [
							'tipo_alerta' => 'reabastecimiento_insuficiente_oc',
							'alerta_insumo' => 'reabastecimiento_insuficiente_oc:' . (int) $insumo->id,
							'orden_compra_id' => (int) $ordenCompra->id,
							'insumo_id' => (int) $insumo->id,
							'stock_actual' => $saldoPosterior,
							'stock_minimo' => (float) $insumo->stock_minimo,
							'origen' => 'ordenes_compra.recibir',
						],
					], 'alerta_insumo', 'reabastecimiento_insuficiente_oc:' . (int) $insumo->id);
				}

				if ($cantidadAceptada < $cantidadRecibida) {
					$notificacionService->crearSiNoExisteHoy([
						'titulo' => 'Recepcion parcial o rechazada',
						'mensaje' => sprintf(
							'La recepcion de %s (%s) en OC %s tuvo diferencia: recibido %.2f, aceptado %.2f.',
							(string) $insumo->nombre,
							(string) $insumo->codigo_insumo,
							(string) $ordenCompra->numero_orden,
							$cantidadRecibida,
							$cantidadAceptada
						),
						'tipo' => 'Alerta',
						'modulo' => 'Compras',
						'prioridad' => 'Alta',
						'user_id' => (int) $usuario->id,
						'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
						'estado' => 'Pendiente',
						'fecha_programada' => now(),
						'requiere_accion' => true,
						'url_accion' => '/ordenes-compra/' . (int) $ordenCompra->id,
						'metadata' => [
							'tipo_alerta' => 'recepcion_parcial_rechazada_oc',
							'alerta_detalle' => 'recepcion_parcial_rechazada_oc:' . (int) $detalle->id,
							'orden_compra_id' => (int) $ordenCompra->id,
							'detalle_id' => (int) $detalle->id,
							'insumo_id' => (int) $insumo->id,
							'origen' => 'ordenes_compra.recibir',
						],
					], 'alerta_detalle', 'recepcion_parcial_rechazada_oc:' . (int) $detalle->id);
				}
			}
		}

		$ordenCompra->update([
			'estado' => 'Recibida',
			'fecha_entrega_real' => $fechaMovimiento,
		]);
	}
}
