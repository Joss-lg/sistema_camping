<?php

namespace App\Http\Controllers; 

use App\Models\ConsumoMaterial;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\TrazabilidadEtapa;
use App\Models\TrazabilidadRegistro;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrazabilidadController extends Controller
{
	/** @var array<int, string> */
	private const ETAPAS_FABRICACION = ['Corte', 'Costura', 'Ensamblado', 'Acabado'];

	public function index(Request $request): View
	{
		abort_unless(PermisoService::canAccessModule($request->user(), 'Trazabilidad'), 403);

		$q = $request->query('q', '');
		$ordenes = $this->obtenerOrdenesTrazabilidad($request);
		$selectedOrdenId = $this->resolverOrdenSeleccionada($request);
		$selectedRegistro = $selectedOrdenId > 0
			? $ordenes->firstWhere('id', $selectedOrdenId)
			: null;

		// Estadísticas
		$statsProductos = $ordenes->count();
		$statsMovimientos = \App\Models\TrazabilidadRegistro::count();

		return view('trazabilidad.index', compact('ordenes', 'statsProductos', 'statsMovimientos', 'q', 'selectedRegistro'));
	}

	public function show(string $codigo): View
	{
		abort_unless(PermisoService::canAccessModule(request()->user(), 'Trazabilidad'), 403);

		$orden = OrdenProduccion::query()
			->with([
				'tipoProducto',
				'user',
				'etapasTrazabilidad.etapaPlantilla',
				'etapasTrazabilidad.responsableArea',
				'etapasTrazabilidad.aprobador',
				'etapasTrazabilidad.registros.user',
				'consumosMateriales.insumo',
				'consumosMateriales.user',
				'productosTerminados.userResponsable',
				'productosTerminados.userInspeccion',
			])
			->where('numero_orden', $codigo)
			->orWhereHas('productosTerminados', function ($query) use ($codigo): void {
				$query->where('numero_lote_produccion', $codigo)
					->orWhere('numero_serie', $codigo)
					->orWhere('codigo_barras', $codigo);
			})
			->firstOrFail();

		$registro = $this->mapOrdenTrazabilidad($orden);

		return view('trazabilidad.show', compact('registro'));
	}

	public function aprobarEtapa(Request $request, int $etapaId): RedirectResponse
	{
		abort_unless(PermisoService::canAccessModule($request->user(), 'Trazabilidad', 'aprobar'), 403);

		DB::beginTransaction();

		try {
			$aprobadorId = (int) ($request->user()?->id ?? 0);

			if ($aprobadorId <= 0) {
				DB::rollBack();
				return back()->with('error', 'No hay un usuario autenticado para aprobar la etapa.');
			}

			$etapa = TrazabilidadEtapa::query()
				->with(['etapaPlantilla', 'ordenProduccion'])
				->lockForUpdate()
				->findOrFail($etapaId);

			$this->authorize('aprobar', $etapa);

			if (! in_array($etapa->estado, ['Esperando Aprobacion', 'Esperando Aprobación'], true)) {
				DB::rollBack();
				return back()->with('error', 'La etapa no esta en estado de Esperando Aprobación.');
			}

			$etapaAnterior = TrazabilidadEtapa::query()
				->where('orden_produccion_id', $etapa->orden_produccion_id)
				->where('numero_secuencia', '<', $etapa->numero_secuencia)
				->orderByDesc('numero_secuencia')
				->lockForUpdate()
				->first();

			if ($etapaAnterior && $etapaAnterior->estado !== 'Finalizada') {
				$etapaAnterior->estado = 'Finalizada';
				$etapaAnterior->fecha_fin_real = $etapaAnterior->fecha_fin_real ?: now();
				$etapaAnterior->save();
			}

			$estadoAnterior = $etapa->estado;
			$etapa->estado = 'En Proceso';
			$etapa->fecha_inicio_real = $etapa->fecha_inicio_real ?: now();
			$etapa->fecha_aprobacion = now();
			$etapa->aprobado_por = $aprobadorId;
			$etapa->save();

			$etapa->registros()->create([
				'orden_produccion_id' => $etapa->orden_produccion_id,
				'user_id' => $aprobadorId,
				'tipo_evento' => TrazabilidadRegistro::EVENTO_APROBACION,
				'estado_anterior' => $estadoAnterior,
				'estado_nuevo' => 'En Proceso',
				'descripcion_evento' => 'Aprobacion manual de etapa por encargado de area.',
				'fecha_evento' => now(),
			]);

			DB::commit();

			return back()->with('ok', 'Etapa aprobada correctamente. La ejecucion quedo En Proceso.');
		} catch (\Throwable $e) {
			DB::rollBack();
			return back()->with('error', 'No fue posible aprobar la etapa: ' . $e->getMessage());
		}
	}

	private function obtenerOrdenesTrazabilidad(Request $request): Collection
	{
		$query = OrdenProduccion::query()
			->where(function ($subQuery): void {
				$subQuery->whereNull('notas')
					->orWhereNotIn('notas', ['Plantilla BOM (no ejecutar)', 'Orden base generada para gestión BOM.']);
			})
			->with([
				'tipoProducto:id,nombre,slug',
				'user:id,name',
				'trazabilidadEtapas.etapaPlantilla:id,nombre',
				'trazabilidadEtapas.responsableArea:id,name',
				'trazabilidadEtapas.aprobador:id,name',
				'consumosMateriales' => fn ($subQuery) => $subQuery
					->with(['insumo:id,nombre', 'user:id,name'])
					->orderByDesc('fecha_consumo')
					->orderByDesc('id'),
				'productosTerminados' => fn ($subQuery) => $subQuery
					->with(['userResponsable:id,name', 'userInspeccion:id,name'])
					->orderByDesc('fecha_produccion'),
			])
			->orderByDesc('fecha_orden')
			->orderByDesc('id');

		if ($request->filled('q')) {
			$search = (string) $request->query('q');

			$query->where(function ($subQuery) use ($search): void {
				$subQuery->where('numero_orden', 'like', '%' . $search . '%')
					->orWhere('notas', 'like', '%' . $search . '%')
					->orWhereHas('tipoProducto', function ($productoQuery) use ($search): void {
						$productoQuery->where('nombre', 'like', '%' . $search . '%')
							->orWhere('slug', 'like', '%' . $search . '%');
					})
					->orWhereHas('productosTerminados', function ($productoQuery) use ($search): void {
						$productoQuery->where('numero_lote_produccion', 'like', '%' . $search . '%')
							->orWhere('numero_serie', 'like', '%' . $search . '%')
							->orWhere('codigo_barras', 'like', '%' . $search . '%');
					});
			});
		}

		return $query
			->limit(150)
			->get()
			->map(fn (OrdenProduccion $orden): object => $this->mapOrdenTrazabilidad($orden));
	}

	private function resolverOrdenSeleccionada(Request $request): int
	{
		$ordenId = (int) $request->query('orden_id', 0);

		if ($ordenId > 0) {
			return $ordenId;
		}

		$productoId = (int) $request->query('producto_id', 0);

		if ($productoId <= 0) {
			return 0;
		}

		return (int) ProductoTerminado::query()
			->whereKey($productoId)
			->value('orden_produccion_id');
	}

	private function mapOrdenTrazabilidad(OrdenProduccion $orden): object
	{
		$producto = $orden->productosTerminados->sortByDesc('fecha_produccion')->first();
		$estado = $this->resolverEstadoVisual($orden, $producto);
		$referencia = $producto?->numero_serie
			?? $producto?->numero_lote_produccion
			?? $orden->numero_orden;
		$stepperEtapas = $this->construirStepperEtapas($orden);
		$lineaTiempo = $this->construirLineaTiempoResumen($orden, $stepperEtapas);

		return (object) [
			'id' => $orden->id,
			'nombre' => $orden->tipoProducto?->nombre ?? 'Orden de producción',
			'numero_orden' => $orden->numero_orden,
			'numero_lote_produccion' => $producto?->numero_lote_produccion,
			'numero_serie' => $producto?->numero_serie,
			'referencia' => $referencia,
			'estado' => $estado,
			'estado_orden' => strtoupper(str_replace(' ', '_', (string) $orden->estado)),
			'linea_tiempo' => $lineaTiempo,
			'stepper_etapas' => $stepperEtapas,
			'producto_terminado_id' => $producto?->id,
			'timeline' => $this->construirTimeline($orden, $producto),
		];
	}

	private function construirTimeline(OrdenProduccion $orden, ?ProductoTerminado $producto): Collection
	{
		$timeline = collect([
			(object) [
				'tipo' => 'orden',
				'clave' => 'orden-' . $orden->id,
				'fecha' => $orden->fecha_orden ?? $orden->created_at,
				'nombre' => 'Orden de producción creada',
				'estado' => OrdenProduccion::normalizarEstadoVisual((string) $orden->estado),
				'notas' => trim((string) $orden->notas) !== ''
					? (string) $orden->notas
					: 'Se registró la orden y se generaron las etapas iniciales de trazabilidad.',
				'responsable' => $orden->user?->name ?? 'Sistema',
				'aprobador' => null,
				'etapa_id' => null,
				'modelo' => null,
			],
		]);

		foreach ($orden->trazabilidadEtapas->sortBy('numero_secuencia') as $etapa) {
			$timeline->push((object) [
				'tipo' => 'etapa',
				'clave' => 'etapa-' . $etapa->id,
				'fecha' => $etapa->fecha_fin_real ?? $etapa->fecha_inicio_real ?? $etapa->fecha_inicio_prevista ?? $etapa->created_at,
				'nombre' => $etapa->etapaPlantilla?->nombre ?? ('Etapa #' . $etapa->numero_secuencia),
				'estado' => $etapa->estado,
				'notas' => $etapa->notas_produccion ?: ($etapa->observaciones_etapa ?: 'Sin observaciones adicionales.'),
				'responsable' => $etapa->responsableArea?->name ?? $orden->user?->name ?? 'Sistema',
				'aprobador' => $etapa->aprobador?->name,
				'etapa_id' => $etapa->id,
				'modelo' => $etapa,
			]);
		}

		if ($orden->trazabilidadEtapas->isEmpty()) {
			$timeline = $timeline->merge($this->construirTimelineEtapasFallback($orden));
		}

		foreach ($orden->consumosMateriales as $consumo) {
			$timeline->push((object) [
				'tipo' => 'consumo',
				'clave' => 'consumo-' . $consumo->id,
				'fecha' => $consumo->fecha_consumo ?? $consumo->created_at,
				'nombre' => 'Consumo de material',
				'estado' => (string) ($consumo->estado_material ?: 'Conforme'),
				'notas' => $this->buildConsumoNotas($consumo),
				'responsable' => $consumo->user?->name ?? $orden->user?->name ?? 'Sistema',
				'aprobador' => null,
				'etapa_id' => null,
				'modelo' => null,
			]);
		}

		if ($producto) {
			$timeline->push((object) [
				'tipo' => 'producto',
				'clave' => 'producto-' . $producto->id,
				'fecha' => $producto->fecha_inspeccion ?? $producto->fecha_finalizacion ?? $producto->fecha_produccion ?? $producto->created_at,
				'nombre' => 'Producto terminado',
				'estado' => $this->resolverEstadoProducto($producto),
				'notas' => $producto->observaciones_calidad ?: ($producto->notas ?: 'Producto generado desde el cierre de la orden.'),
				'responsable' => $producto->userInspeccion?->name ?? $producto->userResponsable?->name ?? $orden->user?->name ?? 'Sistema',
				'aprobador' => $producto->userInspeccion?->name,
				'etapa_id' => null,
				'producto_id' => $producto->id,
				'modelo' => null,
			]);
		}

		return $timeline
			->sortBy(fn (object $item) => $item->fecha?->getTimestamp() ?? 0)
			->values();
	}

	private function resolverEstadoVisual(OrdenProduccion $orden, ?ProductoTerminado $producto): string
	{
		if ($producto) {
			return $this->resolverEstadoProducto($producto);
		}

		$etapaEsperando = $orden->trazabilidadEtapas
			->first(fn (TrazabilidadEtapa $etapa): bool => in_array($etapa->estado, ['Esperando Aprobacion', 'Esperando Aprobación'], true));

		if ($etapaEsperando) {
			return $etapaEsperando->estado;
		}

		$etapaEnProceso = $orden->trazabilidadEtapas
			->first(fn (TrazabilidadEtapa $etapa): bool => $etapa->estado === 'En Proceso');

		if ($etapaEnProceso) {
			return 'En Proceso';
		}

		$etapaFinalizada = $orden->trazabilidadEtapas
			->every(fn (TrazabilidadEtapa $etapa): bool => $etapa->estado === 'Finalizada');

		if ($orden->trazabilidadEtapas->isNotEmpty() && $etapaFinalizada) {
			return 'Finalizada';
		}

		$estadoOrden = mb_strtolower((string) $orden->estado);

		if (str_contains($estadoOrden, 'proceso')) {
			return 'En Proceso';
		}

		if (str_contains($estadoOrden, 'finalizada') || str_contains($estadoOrden, 'completada')) {
			return 'Finalizada';
		}

		return 'Pendiente';
	}

	private function resolverEstadoProducto(ProductoTerminado $producto): string
	{
		if ((string) $producto->estado_calidad === ProductoTerminado::ESTADO_CALIDAD_PENDIENTE) {
			return ProductoTerminado::ESTADO_CALIDAD_PENDIENTE;
		}

		return (string) $producto->estado;
	}

	private function construirTimelineEtapasFallback(OrdenProduccion $orden): Collection
	{
		$etapaActual = (string) ($orden->etapa_fabricacion_actual ?: self::ETAPAS_FABRICACION[0]);
		$estadoOrden = mb_strtolower((string) $orden->estado);
		$posicionActual = array_search($etapaActual, self::ETAPAS_FABRICACION, true);
		$posicionActual = $posicionActual === false ? 0 : $posicionActual;

		return collect(self::ETAPAS_FABRICACION)
			->values()
			->map(function (string $etapa, int $index) use ($orden, $posicionActual, $estadoOrden): object {
				$estado = 'Pendiente';

				if (str_contains($estadoOrden, 'finalizada') || str_contains($estadoOrden, 'completada')) {
					$estado = 'Finalizada';
				} elseif (str_contains($estadoOrden, 'proceso')) {
					$estado = $index < $posicionActual
						? 'Finalizada'
						: ($index === $posicionActual ? 'En Proceso' : 'Pendiente');
				}

				return (object) [
					'tipo' => 'etapa',
					'clave' => 'etapa-fallback-' . $orden->id . '-' . $index,
					'fecha' => $orden->updated_at,
					'nombre' => $etapa,
					'estado' => $estado,
					'notas' => 'Etapa estimada desde el seguimiento operativo de producción.',
					'responsable' => $orden->user?->name ?? 'Sistema',
					'aprobador' => null,
					'etapa_id' => null,
					'modelo' => null,
				];
			});
	}

	private function buildConsumoNotas(ConsumoMaterial $consumo): string
	{
		$material = $consumo->insumo?->nombre ?: 'Material';
		$cantidad = number_format((float) $consumo->cantidad_consumida, 2);
		$merma = number_format((float) $consumo->cantidad_desperdicio, 2);
		$base = sprintf('%s consumido: %s | Merma: %s', $material, $cantidad, $merma);

		if (trim((string) $consumo->observaciones) !== '') {
			return $base . ' | ' . trim((string) $consumo->observaciones);
		}

		return $base;
	}

	private function construirStepperEtapas(OrdenProduccion $orden): Collection
	{
		$etapasTraza = $orden->trazabilidadEtapas
			->sortBy('numero_secuencia')
			->values();

		if ($etapasTraza->isNotEmpty()) {
			return $etapasTraza->map(function (TrazabilidadEtapa $etapa, int $index): object {
				$estado = (string) ($etapa->estado ?: 'Pendiente');
				$estadoNormalizado = mb_strtolower($estado);

				$estadoUi = match (true) {
					str_contains($estadoNormalizado, 'finalizada') => 'finalizada',
					str_contains($estadoNormalizado, 'esperando aprobacion'),
					str_contains($estadoNormalizado, 'esperando aprobación') => 'bloqueada',
					str_contains($estadoNormalizado, 'proceso') => 'actual',
					default => 'pendiente',
				};

				return (object) [
					'numero' => $index + 1,
					'nombre' => $etapa->etapaPlantilla?->nombre ?? ('Etapa #' . $etapa->numero_secuencia),
					'estado' => $estado,
					'estado_ui' => $estadoUi,
					'etapa_id' => $etapa->id,
					'modelo' => $etapa,
				];
			});
		}

		$etapaActual = (string) ($orden->etapa_fabricacion_actual ?: self::ETAPAS_FABRICACION[0]);
		$posicionActual = array_search($etapaActual, self::ETAPAS_FABRICACION, true);
		$posicionActual = $posicionActual === false ? 0 : $posicionActual;
		$estadoOrden = mb_strtolower((string) $orden->estado);

		return collect(self::ETAPAS_FABRICACION)
			->values()
			->map(function (string $nombreEtapa, int $index) use ($estadoOrden, $posicionActual): object {
				$estadoUi = 'pendiente';
				$estado = 'Pendiente';

				if (str_contains($estadoOrden, 'finalizada') || str_contains($estadoOrden, 'completada')) {
					$estadoUi = 'finalizada';
					$estado = 'Finalizada';
				} elseif (str_contains($estadoOrden, 'proceso')) {
					$estadoUi = $index < $posicionActual
						? 'finalizada'
						: ($index === $posicionActual ? 'actual' : 'pendiente');

					$estado = match ($estadoUi) {
						'finalizada' => 'Finalizada',
						'actual' => 'En Proceso',
						default => 'Pendiente',
					};
				}

				return (object) [
					'numero' => $index + 1,
					'nombre' => $nombreEtapa,
					'estado' => $estado,
					'estado_ui' => $estadoUi,
					'etapa_id' => null,
					'modelo' => null,
				];
			});
	}

	private function construirLineaTiempoResumen(OrdenProduccion $orden, Collection $stepperEtapas): object
	{
		$indiceActual = $stepperEtapas->search(fn ($etapa): bool => in_array((string) $etapa->estado_ui, ['actual', 'bloqueada'], true));

		return (object) [
			'fuente' => $orden->trazabilidadEtapas->isNotEmpty() ? 'trazabilidad' : 'fabricacion',
			'paso_actual' => $indiceActual === false ? $stepperEtapas->count() : ($indiceActual + 1),
			'total_pasos' => $stepperEtapas->count(),
			'etapa_actual' => (string) ($orden->etapa_fabricacion_actual ?: self::ETAPAS_FABRICACION[0]),
		];
	}
}
