<?php

namespace App\Http\Controllers; 

use App\Models\ProductoTerminado;
use App\Models\TrazabilidadEtapa;
use App\Models\TrazabilidadRegistro;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrazabilidadController extends Controller
{
	public function index(Request $request): View
	{
		abort_unless(PermisoService::canAccessModule($request->user(), 'Trazabilidad'), 403);

		$q = $request->query('q', '');
		$selectedProducto = null;

		$query = ProductoTerminado::query()
			->with(['tipoProducto', 'unidadMedida', 'ordenProduccion'])
			->orderByDesc('fecha_produccion');

		// Mostrar productos terminados y en proceso
		$query->whereIn('estado', ['APROBADO', 'EN_PROCESO', 'PENDIENTE']);

		if ($request->filled('q')) {
			$search = (string) $request->query('q');
			$query->where(function ($subQuery) use ($search) {
				$subQuery->where('numero_lote_produccion', 'like', '%' . $search . '%')
					->orWhere('numero_serie', 'like', '%' . $search . '%')
					->orWhere('codigo_barras', 'like', '%' . $search . '%');
			});
		}

		if ($request->filled('estado')) {
			$query->where('estado', $request->query('estado'));
		}

		if ($request->filled('tipo_producto_id')) {
			$query->where('tipo_producto_id', $request->query('tipo_producto_id'));
		}

		$productos = $query->paginate(15)->withQueryString();

		if ($request->filled('producto_id')) {
			$selectedProducto = ProductoTerminado::query()
				->with([
					'tipoProducto',
					'etapasTrazabilidad.etapaPlantilla',
					'etapasTrazabilidad.responsableArea',
					'etapasTrazabilidad.aprobador',
					'etapasTrazabilidad.ordenProduccion.user',
				])
				->whereKey((int) $request->query('producto_id'))
				->first();
		}

		// Estadísticas
		$statsProductos = ProductoTerminado::count();
		$statsMovimientos = \App\Models\TrazabilidadRegistro::count();

		return view('trazabilidad.index', compact('productos', 'statsProductos', 'statsMovimientos', 'q', 'selectedProducto'));
	}

	public function show(string $codigo): View
	{
		abort_unless(PermisoService::canAccessModule(request()->user(), 'Trazabilidad'), 403);

		$producto = ProductoTerminado::query()
			->where('numero_lote_produccion', $codigo)
			->orWhere('numero_serie', $codigo)
			->orWhere('codigo_barras', $codigo)
			->with([
				'tipoProducto',
				'unidadMedida',
				'userResponsable',
				'ordenProduccion',
				'etapasTrazabilidad.etapaPlantilla',
				'etapasTrazabilidad.responsableArea',
				'etapasTrazabilidad.aprobador',
				'etapasTrazabilidad.registros.user',
				'etapasTrazabilidad.ordenProduccion.user',
			])
			->firstOrFail();

		return view('trazabilidad.show', compact('producto'));
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
}
