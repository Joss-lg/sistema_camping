<?php

namespace App\Http\Controllers;

use App\Events\OrdenProduccionCreada;
use App\Models\ConsumoMaterial;
use App\Models\InventarioProductoTerminado;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenCompra;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use App\Models\ProductoTerminado;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
use App\Models\UbicacionAlmacen;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProduccionController extends Controller
{
    private const BOM_TEMPLATE_NOTE = 'Plantilla BOM (no ejecutar)';
    private const BOM_TEMPLATE_LEGACY_NOTE = 'Orden base generada para gestión BOM.';

    /** @var array<int, \Illuminate\Support\Collection<int, OrdenProduccionMaterial>> */
    private array $lineasBomCachePorProducto = [];

    /** @var array<int, string> */
    private const ETAPAS_FABRICACION = ['Corte', 'Costura', 'Ensamblado', 'Acabado'];

    /** @var array<int, string> */
    private const TURNOS = ['Manana', 'Tarde', 'Noche'];

    /** @var array<int, string> */
    private const TIPOS_MERMA = ['Corte', 'Costura', 'Defecto', 'Manejo', 'Otro'];

    public function index(): View
    {
        abort_unless(PermisoService::canAccessModule(Auth::user(), 'Produccion'), 403);

        $canManage = $this->canManage();

        $productos = TipoProducto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (TipoProducto $producto): object => (object) [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->slug,
            ]);

        $usuarios = User::query()
            ->where('activo', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $usuario): object => (object) [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
            ]);

        $materiales = Insumo::query()
            ->with('proveedor:id,razon_social,nombre_comercial')
            ->where('activo', true)
            ->orderBy('nombre')
            ->limit(300)
            ->get()
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
                'stock' => (float) $material->stock_actual,
                'proveedor' => (object) [
                    'nombre' => $material->proveedor?->nombre_comercial ?: $material->proveedor?->razon_social,
                ],
            ]);

        $ordenesRaw = $this->buildOrdenesQuery()->get();
        $ordenes = $ordenesRaw->map(fn (OrdenProduccion $orden): object => $this->mapOrdenForView($orden));

        $statsOrdenes = $ordenesRaw->count();
        $statsEnProceso = $ordenesRaw->filter(fn (OrdenProduccion $o): bool => (string) $o->estado === OrdenProduccion::ESTADO_EN_PROCESO)->count();
        $statsFinalizadas = $ordenesRaw->filter(function (OrdenProduccion $o): bool {
            return OrdenProduccion::esEstadoFinalizado((string) $o->estado);
        })->count();

        $consumosPeriodo = ConsumoMaterial::query()
            ->whereDate('fecha_consumo', '>=', now()->startOfMonth()->toDateString())
            ->get(['cantidad_consumida', 'cantidad_desperdicio']);

        $statsMerma = round((float) $consumosPeriodo->sum('cantidad_desperdicio'), 2);
        $totalConsumidoPeriodo = (float) $consumosPeriodo->sum(function (ConsumoMaterial $consumo): float {
            return (float) $consumo->cantidad_consumida + (float) $consumo->cantidad_desperdicio;
        });
        $statsMermaPorcentaje = $totalConsumidoPeriodo > 0
            ? round(($statsMerma / $totalConsumidoPeriodo) * 100, 2)
            : 0.0;

        $etapasFabricacion = self::ETAPAS_FABRICACION;
        $turnos = self::TURNOS;
        $tiposMerma = self::TIPOS_MERMA;

        return view('produccion.index', compact(
            'canManage',
            'productos',
            'usuarios',
            'materiales',
            'ordenes',
            'statsOrdenes',
            'statsEnProceso',
            'statsFinalizadas',
            'statsMerma',
            'statsMermaPorcentaje',
            'etapasFabricacion',
            'turnos',
            'tiposMerma'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_esperada' => ['nullable', 'date'],
            'etapa_fabricacion_actual' => ['nullable', 'in:Corte,Costura,Ensamblado,Acabado'],
            'maquina_asignada' => ['nullable', 'string', 'max:120'],
            'turno_asignado' => ['nullable', 'in:Manana,Tarde,Noche'],
        ]);

        $unidadId = UnidadMedida::query()->where('activo', true)->value('id')
            ?? UnidadMedida::query()->value('id');

        if (! $unidadId) {
            return back()->withErrors(['producto_id' => 'No existe una unidad de medida activa para crear la orden.'])->withInput();
        }

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => (int) $data['producto_id'],
            'user_id' => (int) $data['responsable_id'],
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => $data['fecha_inicio'] ? Carbon::parse($data['fecha_inicio'])->toDateString() : now()->toDateString(),
            'fecha_fin_prevista' => $data['fecha_esperada'] ? Carbon::parse($data['fecha_esperada'])->toDateString() : now()->addDay()->toDateString(),
            'cantidad_produccion' => (float) $data['cantidad'],
            'unidad_medida_id' => (int) $unidadId,
            'estado' => OrdenProduccion::ESTADO_PENDIENTE,
            'etapa_fabricacion_actual' => $data['etapa_fabricacion_actual'] ?? 'Corte',
            'maquina_asignada' => $data['maquina_asignada'] ?? null,
            'turno_asignado' => $data['turno_asignado'] ?? null,
            'prioridad' => 'Normal',
            'requiere_calidad' => true,
            'etapas_totales' => 0,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ]);

        // Al crear una orden operativa se replica su receta BOM para habilitar seguimiento y consumo por material.
        $this->sincronizarMaterialesDesdeBom($orden);

        event(new OrdenProduccionCreada($orden));

        return redirect()->route('produccion.index')->with('ok', 'Orden de producción creada correctamente.');
    }

    public function registrarConsumo(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validate([
            'orden_produccion_id' => ['required', 'integer', 'exists:ordenes_produccion,id'],
            'material_id' => ['required', 'integer', 'exists:insumos,id'],
            'cantidad_usada' => ['required', 'numeric', 'gt:0'],
            'cantidad_merma' => ['nullable', 'numeric', 'gte:0'],
            'tipo_merma' => ['nullable', 'in:Corte,Costura,Defecto,Manejo,Otro'],
            'motivo_merma' => ['nullable', 'string', 'max:500'],
        ]);

        $orden = OrdenProduccion::query()->findOrFail((int) $data['orden_produccion_id']);

        if ($this->ordenBloqueadaPorAprobacion($orden)) {
            return back()
                ->withErrors(['orden_produccion_id' => 'La orden tiene una etapa en Esperando Aprobacion. Debe firmarse antes de registrar consumos.'])
                ->withInput();
        }

        $material = Insumo::query()->findOrFail((int) $data['material_id']);

        // Si existe desfase stock_global vs stock_en_lotes, crea un lote de ajuste para no bloquear consumo válido.
        $this->regularizarDesfaseLotes(collect([$material]));

        // Backfill para órdenes antiguas que no tenían líneas de receta asignadas.
        $this->sincronizarMaterialesDesdeBom($orden);

        $lote = $this->obtenerLoteDisponibleParaConsumo((int) $material->id);

        if (! $lote) {
            return back()->withErrors([
                'material_id' => sprintf(
                    'No hay lote disponible con stock para el material seleccionado (%s). Verifica recepción de lotes para este insumo.',
                    (string) $material->nombre
                ),
            ])->withInput();
        }

        $cantidadUsada = (float) $data['cantidad_usada'];
        $cantidadMerma = (float) ($data['cantidad_merma'] ?? 0);
        $total = $cantidadUsada + $cantidadMerma;

        $lineaMaterial = OrdenProduccionMaterial::query()
            ->where('orden_produccion_id', $orden->id)
            ->where('insumo_id', $material->id)
            ->first();

        if (! $lineaMaterial) {
            $lineasBomProducto = $this->obtenerLineasBomPorProducto((int) $orden->tipo_producto_id)
                ->filter(fn (OrdenProduccionMaterial $linea): bool => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado');

            if ($lineasBomProducto->isNotEmpty()) {
                return back()->withErrors([
                    'material_id' => 'El material seleccionado no está asignado a la receta de esta orden.',
                ])->withInput();
            }

            // Compatibilidad legacy: si no existe receta BOM para el producto, se crea una línea mínima de control.
            $lineaMaterial = OrdenProduccionMaterial::query()->create([
                'orden_produccion_id' => $orden->id,
                'insumo_id' => $material->id,
                'unidad_medida_id' => (int) $material->unidad_medida_id,
                'cantidad_necesaria' => max(0.0001, $total),
                'cantidad_utilizada' => 0,
                'cantidad_desperdicio' => 0,
                'estado_asignacion' => 'Asignado',
                'notas_asignacion' => 'Línea auto-creada por compatibilidad legacy al registrar consumo.',
                'numero_linea' => 999,
            ]);
        }

        if ($cantidadMerma > 0 && trim((string) ($data['motivo_merma'] ?? '')) === '') {
            return back()->withErrors(['motivo_merma' => 'Debes capturar motivo de merma cuando exista desperdicio.'])->withInput();
        }

        if ($total > (float) $material->stock_actual) {
            return back()->withErrors(['cantidad_usada' => 'El consumo total excede el stock actual del material.'])->withInput();
        }

        if ($total > (float) $lote->cantidadDisponible()) {
            return back()->withErrors(['cantidad_usada' => 'El consumo total excede la disponibilidad del lote.'])->withInput();
        }

        DB::transaction(function () use ($orden, $material, $lineaMaterial, $lote, $cantidadUsada, $cantidadMerma, $total, $data): void {
            $tipoMerma = (string) ($data['tipo_merma'] ?? 'Otro');
            $observaciones = $data['motivo_merma'] ?? null;

            if ($cantidadMerma > 0) {
                $observaciones = trim(sprintf('[%s] %s', $tipoMerma, (string) $observaciones));
            }

            ConsumoMaterial::query()->create([
                'orden_produccion_id' => $orden->id,
                'insumo_id' => $material->id,
                'lote_insumo_id' => $lote->id,
                'unidad_medida_id' => $material->unidad_medida_id,
                'cantidad_consumida' => $cantidadUsada,
                'cantidad_desperdicio' => $cantidadMerma,
                'user_id' => Auth::id() ?? $orden->user_id,
                'fecha_consumo' => now(),
                'estado_material' => $cantidadMerma > 0 ? 'No Conforme' : 'Conforme',
                'observaciones' => $observaciones,
                'requiere_revision' => $cantidadMerma > 0,
                'numero_lote_produccion' => $orden->numero_orden,
            ]);

            $material->stock_actual = max(0, (float) $material->stock_actual - $total);
            $material->save();

            $lote->cantidad_consumida = (float) $lote->cantidad_consumida + $total;
            $lote->cantidad_en_stock = max(0, (float) $lote->cantidad_en_stock - $total);
            $lote->save();

            $lineaMaterial->cantidad_utilizada = (float) $lineaMaterial->cantidad_utilizada + $cantidadUsada;
            $lineaMaterial->cantidad_desperdicio = (float) $lineaMaterial->cantidad_desperdicio + $cantidadMerma;

            $consumoAcumulado = (float) $lineaMaterial->cantidad_utilizada + (float) $lineaMaterial->cantidad_desperdicio;
            $lineaMaterial->estado_asignacion = $consumoAcumulado >= (float) $lineaMaterial->cantidad_necesaria
                ? 'Consumido'
                : 'Parcial';
            $lineaMaterial->save();
        });

        if ($request->boolean('redirect_seguimiento')) {
            return redirect()
                ->route('produccion.seguimiento', ['id' => $orden->id])
                ->with('ok', 'Consumo de material registrado correctamente.');
        }

        return redirect()->route('produccion.index')->with('ok', 'Consumo de material registrado correctamente.');
    }

    public function updateSeguimiento(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validate([
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'maquina_asignada' => ['nullable', 'string', 'max:120'],
            'turno_asignado' => ['nullable', 'in:Manana,Tarde,Noche'],
            'estado' => ['nullable', 'in:PENDIENTE,EN_PROCESO,FINALIZADA'],
            'cantidad_completada' => ['nullable', 'numeric', 'gte:0'],
            'etapa_fabricacion_actual' => ['nullable', 'in:Corte,Costura,Ensamblado,Acabado'],
        ]);

        $orden = OrdenProduccion::query()->findOrFail($id);
        $bloqueadaAprobacion = $this->ordenBloqueadaPorAprobacion($orden);

        DB::transaction(function () use ($orden, $data, $bloqueadaAprobacion): void {
            $orden->user_id = (int) $data['responsable_id'];
            $orden->maquina_asignada = $data['maquina_asignada'] ?? null;
            $orden->turno_asignado = $data['turno_asignado'] ?? null;
            $orden->save();

            $etapaActiva = $orden->trazabilidadEtapas()
                ->whereIn('estado', ['Pendiente', 'En Proceso', 'Esperando Aprobacion', 'Esperando Aprobación'])
                ->orderBy('numero_secuencia')
                ->first();

            if ($etapaActiva && ! $etapaActiva->responsable_id) {
                $etapaActiva->responsable_id = (int) $data['responsable_id'];
                $etapaActiva->save();
            }

            if ($bloqueadaAprobacion || empty($data['estado'])) {
                return;
            }

            $estado = match ($data['estado']) {
                'EN_PROCESO' => OrdenProduccion::ESTADO_EN_PROCESO,
                'FINALIZADA' => OrdenProduccion::ESTADO_FINALIZADA,
                default => OrdenProduccion::ESTADO_PENDIENTE,
            };

            $eraFinalizada = OrdenProduccion::esEstadoFinalizado((string) $orden->estado);

            $cantidadCompletada = (float) ($data['cantidad_completada'] ?? 0);
            $porcentaje = (float) ($orden->cantidad_produccion > 0
                ? min(100, max(0, ($cantidadCompletada / (float) $orden->cantidad_produccion) * 100))
                : 0);

            if (OrdenProduccion::esEstadoFinalizado($estado)) {
                $orden->etapa_fabricacion_actual = 'Acabado';
                $orden->marcarCompletada();

                return;
            }

            $orden->estado = $estado;
            $orden->porcentaje_completado = $porcentaje;
            if (! empty($data['etapa_fabricacion_actual'])) {
                $orden->etapa_fabricacion_actual = (string) $data['etapa_fabricacion_actual'];
            }

            if ($estado === OrdenProduccion::ESTADO_EN_PROCESO && ! $orden->fecha_inicio_real) {
                $orden->fecha_inicio_real = now()->toDateString();
            }

            if ($eraFinalizada) {
                $orden->fecha_fin_real = null;
                $orden->etapas_completadas = 0;
                $orden->etapa_fabricacion_actual = $orden->etapa_fabricacion_actual ?: 'Corte';
                $this->ocultarTerminadosDeOrdenReabierta($orden->id);
            }

            $orden->save();
        });

        if ($bloqueadaAprobacion) {
            return back()->with('ok', 'Asignación actualizada. El estado no se modificó porque la orden está bloqueada por aprobación pendiente.');
        }

        return back()->with('ok', 'Seguimiento actualizado correctamente.');
    }

    private function ocultarTerminadosDeOrdenReabierta(int $ordenId): void
    {
        $productoIds = ProductoTerminado::query()
            ->where('orden_produccion_id', $ordenId)
            ->pluck('id');

        if ($productoIds->isEmpty()) {
            return;
        }

        InventarioProductoTerminado::query()
            ->whereIn('producto_terminado_id', $productoIds)
            ->delete();

        ProductoTerminado::query()
            ->whereIn('id', $productoIds)
            ->delete();
    }

    public function cancelar(int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule(request()->user(), 'Produccion', 'editar'), 403);

        $orden = OrdenProduccion::query()->findOrFail($id);
        $orden->estado = OrdenProduccion::ESTADO_CANCELADA;
        $orden->save();

        return redirect()->route('produccion.index')->with('ok', 'Orden cancelada correctamente.');
    }

    public function ordenesFiltradas(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion'), 403);

        $canManage = $this->canManage();
        $usuarios = User::query()
            ->where('activo', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $usuario): object => (object) [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
            ]);

        $query = $this->buildOrdenesQuery();

        if ($request->filled('responsable_id')) {
            $query->where('user_id', (int) $request->query('responsable_id'));
        }

        $ordenes = $query->get()->map(fn (OrdenProduccion $orden): object => $this->mapOrdenForView($orden));

        return view('produccion.partials.tabla_ordenes', compact('ordenes', 'canManage', 'usuarios'));
    }

    public function seguimiento(Request $request, int $id): View
    {
        abort_unless(PermisoService::canAccessModule(Auth::user(), 'Produccion'), 403);

        $canManage = $this->canManage();

        $usuarios = User::query()
            ->where('activo', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $usuario): object => (object) [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
            ]);

        $orden = $this->buildOrdenesQuery()
            ->whereKey($id)
            ->firstOrFail();

        $ordenView = $this->mapOrdenForView($orden);
        $stepperEtapas = $this->construirStepperEtapas($orden, $ordenView);

        $filtros = (object) [
            'material_id' => $request->query('material_id'),
            'usuario_id' => $request->query('usuario_id'),
            'estado_material' => $request->query('estado_material'),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
        ];

        $historialConsumosQuery = ConsumoMaterial::query()
            ->with(['insumo:id,nombre', 'user:id,name'])
            ->where('orden_produccion_id', $orden->id);

        if (! empty($filtros->material_id)) {
            $historialConsumosQuery->where('insumo_id', (int) $filtros->material_id);
        }

        if (! empty($filtros->usuario_id)) {
            $historialConsumosQuery->where('user_id', (int) $filtros->usuario_id);
        }

        if (! empty($filtros->estado_material)) {
            $historialConsumosQuery->where('estado_material', (string) $filtros->estado_material);
        }

        if (! empty($filtros->desde)) {
            $historialConsumosQuery->whereDate('fecha_consumo', '>=', Carbon::parse((string) $filtros->desde)->toDateString());
        }

        if (! empty($filtros->hasta)) {
            $historialConsumosQuery->whereDate('fecha_consumo', '<=', Carbon::parse((string) $filtros->hasta)->toDateString());
        }

        $historialConsumos = $historialConsumosQuery
            ->orderByDesc('fecha_consumo')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ConsumoMaterial $consumo): object => (object) [
                'id' => (int) $consumo->id,
                'fecha' => $consumo->fecha_consumo ?? $consumo->created_at,
                'material' => $consumo->insumo?->nombre,
                'cantidad_usada' => (float) $consumo->cantidad_consumida,
                'cantidad_merma' => (float) $consumo->cantidad_desperdicio,
                'estado_material' => (string) ($consumo->estado_material ?: 'Conforme'),
                'usuario' => $consumo->user?->name,
                'observaciones' => $consumo->observaciones,
            ]);

        $resumenConsumos = (object) [
            'eventos' => $historialConsumos->count(),
            'cantidad_total' => round((float) $historialConsumos->sum('cantidad_usada'), 2),
            'merma_total' => round((float) $historialConsumos->sum('cantidad_merma'), 2),
        ];

        $resumenConsumos->merma_porcentaje = $resumenConsumos->cantidad_total + $resumenConsumos->merma_total > 0
            ? round(($resumenConsumos->merma_total / ($resumenConsumos->cantidad_total + $resumenConsumos->merma_total)) * 100, 2)
            : 0.0;

        $materialesFiltro = ConsumoMaterial::query()
            ->with('insumo:id,nombre')
            ->where('orden_produccion_id', $orden->id)
            ->get()
            ->pluck('insumo')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values()
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
            ]);

        $usuariosFiltro = ConsumoMaterial::query()
            ->with('user:id,name')
            ->where('orden_produccion_id', $orden->id)
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (User $usuario): object => (object) [
                'id' => $usuario->id,
                'nombre' => $usuario->name,
            ]);

        $indiceActual = $stepperEtapas->search(fn ($etapa): bool => in_array((string) $etapa->estado_ui, ['actual', 'bloqueada'], true));
        $lineaTiempo = (object) [
            'fuente' => $orden->trazabilidadEtapas->isNotEmpty() ? 'trazabilidad' : 'fabricacion',
            'paso_actual' => $indiceActual === false ? $stepperEtapas->count() : ($indiceActual + 1),
            'total_pasos' => $stepperEtapas->count(),
            'etapa_actual' => $ordenView->etapa_fabricacion_actual,
        ];

        $stockPorLotes = LoteInsumo::query()
            ->selectRaw('insumo_id, SUM(cantidad_en_stock) as stock_lotes')
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', 'Rechazado');
            })
            ->groupBy('insumo_id')
            ->pluck('stock_lotes', 'insumo_id');

        $materialesConsumo = Insumo::query()
            ->where('activo', true)
            ->whereIn('id', $ordenView->materiales_ids)
            ->orderBy('nombre')
            ->get();

        $this->regularizarDesfaseLotes($materialesConsumo);

        $stockPorLotes = LoteInsumo::query()
            ->selectRaw('insumo_id, SUM(cantidad_en_stock) as stock_lotes')
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', 'Rechazado');
            })
            ->groupBy('insumo_id')
            ->pluck('stock_lotes', 'insumo_id');

        $materialesConsumo = $materialesConsumo
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
                'stock' => (float) $material->stock_actual,
                'stock_lotes' => round((float) ($stockPorLotes[$material->id] ?? 0), 4),
            ]);

        $materialesBloqueados = $materialesConsumo
            ->filter(fn ($material): bool => $material->stock_lotes <= 0)
            ->values();

        $materialesConsumo = $materialesConsumo
            ->filter(fn ($material): bool => $material->stock_lotes > 0)
            ->values();

        $puedeRegistrarConsumo = ! empty($ordenView->materiales_ids) && $materialesConsumo->isNotEmpty();

        $tiposMerma = self::TIPOS_MERMA;

        return view('produccion.seguimiento', compact(
            'canManage',
            'ordenView',
            'usuarios',
            'stepperEtapas',
            'lineaTiempo',
            'materialesConsumo',
            'materialesBloqueados',
            'puedeRegistrarConsumo',
            'tiposMerma',
            'historialConsumos',
            'resumenConsumos',
            'materialesFiltro',
            'usuariosFiltro',
            'filtros'
        ));
    }

    public function bomIndex(): View
    {
        abort_unless(PermisoService::canAccessModule(Auth::user(), 'Produccion'), 403);

        $canManage = $this->canManage();

        $productos = TipoProducto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (TipoProducto $producto): object => (object) [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->slug,
            ]);

        $materiales = Insumo::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
                'stock' => (float) $material->stock_actual,
            ]);

        $recetas = OrdenProduccionMaterial::query()
            ->with(['ordenProduccion.tipoProducto:id,nombre,slug', 'insumo:id,nombre'])
            ->whereHas('ordenProduccion', function ($query): void {
                $query->whereIn('notas', [self::BOM_TEMPLATE_NOTE, self::BOM_TEMPLATE_LEGACY_NOTE]);
            })
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get()
            ->groupBy('orden_produccion_id')
            ->map(function ($lineas): object {
                /** @var OrdenProduccionMaterial|null $referencia */
                $referencia = $lineas->first();

                return (object) [
                    // Id de receta (plantilla BOM), no id de línea individual.
                    'id' => $referencia?->orden_produccion_id,
                    'producto' => (object) [
                        'nombre' => $referencia?->ordenProduccion?->tipoProducto?->nombre,
                        'sku' => $referencia?->ordenProduccion?->tipoProducto?->slug,
                    ],
                    'materiales' => $lineas
                        ->map(fn (OrdenProduccionMaterial $linea): object => (object) [
                            'nombre' => $linea->insumo?->nombre,
                            'cantidad_base' => (float) $linea->cantidad_necesaria,
                            'activo' => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado',
                        ])
                        ->values(),
                    'activo' => $lineas->contains(fn (OrdenProduccionMaterial $linea): bool => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado'),
                    'updated_at' => $lineas->max('updated_at'),
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return view('produccion.bom', compact('canManage', 'productos', 'materiales', 'recetas'));
    }

    public function bomStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['required', 'integer', 'exists:insumos,id'],
            'cantidad_base' => ['required', 'array', 'min:1'],
            'cantidad_base.*' => ['required', 'numeric', 'gt:0'],
            'activo' => ['nullable', 'array'],
            'activo.*' => ['nullable', 'in:0,1'],
        ]);

        $unidadId = UnidadMedida::query()->where('activo', true)->value('id')
            ?? UnidadMedida::query()->value('id');

        if (! $unidadId) {
            return back()->withErrors(['producto_id' => 'No existe unidad de medida para registrar BOM.'])->withInput();
        }

        DB::transaction(function () use ($data, $unidadId): void {
            $ordenBase = $this->obtenerOrdenPlantillaBom((int) $data['producto_id'], (int) $unidadId);

            foreach ($data['material_id'] as $i => $materialId) {
                $insumo = Insumo::query()->find((int) $materialId);

                if (! $insumo) {
                    continue;
                }

                $cantidadBase = (float) ($data['cantidad_base'][$i] ?? 0);
                $isActivo = (string) ($data['activo'][$i] ?? '1') === '1';

                $linea = OrdenProduccionMaterial::query()
                    ->where('orden_produccion_id', $ordenBase->id)
                    ->where('insumo_id', $insumo->id)
                    ->first();

                if ($linea) {
                    $linea->update([
                        'unidad_medida_id' => (int) $insumo->unidad_medida_id,
                        'cantidad_necesaria' => $cantidadBase,
                        'estado_asignacion' => $isActivo ? 'Asignado' : 'Pendiente',
                        'notas_asignacion' => 'Actualizado desde BOM.',
                    ]);
                } else {
                    OrdenProduccionMaterial::query()->create([
                        'orden_produccion_id' => $ordenBase->id,
                        'insumo_id' => $insumo->id,
                        'unidad_medida_id' => (int) $insumo->unidad_medida_id,
                        'cantidad_necesaria' => $cantidadBase,
                        'cantidad_utilizada' => 0,
                        'cantidad_desperdicio' => 0,
                        'estado_asignacion' => $isActivo ? 'Asignado' : 'Pendiente',
                        'notas_asignacion' => 'Creado desde BOM.',
                        'numero_linea' => $i + 1,
                    ]);
                }
            }
        });

        return redirect()->route('produccion.bom.index')->with('ok', 'Líneas de BOM guardadas correctamente.');
    }

    private function canManage(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $rol = mb_strtolower((string) ($user->role?->slug ?: $user->role?->nombre ?: ''));

        return in_array($rol, ['admin', 'super_admin', 'super-admin', 'super administrador', 'almacen', 'almacén'], true)
            || $user->canCustom('Produccion', 'crear');
    }

    private function buildOrdenesQuery()
    {
        return OrdenProduccion::query()
            ->where(function ($query): void {
                $query->whereNull('notas')
                    ->orWhereNotIn('notas', [self::BOM_TEMPLATE_NOTE, self::BOM_TEMPLATE_LEGACY_NOTE]);
            })
            ->with([
                'tipoProducto:id,nombre,slug',
                'user:id,name',
                'materiales.insumo:id,nombre',
                'consumosMateriales.insumo:id,nombre',
                'consumosMateriales.user:id,name',
                'trazabilidadEtapas:id,orden_produccion_id,etapa_plantilla_id,numero_secuencia,estado',
                'trazabilidadEtapas.etapaPlantilla:id,nombre',
            ])
            ->orderByDesc('updated_at')
            ->limit(150);
    }

    private function obtenerOrdenPlantillaBom(int $productoId, int $unidadId): OrdenProduccion
    {
        $orden = OrdenProduccion::query()
            ->where('tipo_producto_id', $productoId)
            ->whereIn('notas', [self::BOM_TEMPLATE_NOTE, self::BOM_TEMPLATE_LEGACY_NOTE])
            ->orderByDesc('id')
            ->first();

        if ($orden) {
            if ((string) $orden->notas !== self::BOM_TEMPLATE_NOTE || (string) $orden->estado !== OrdenProduccion::ESTADO_CANCELADA) {
                $orden->update([
                    'notas' => self::BOM_TEMPLATE_NOTE,
                    'estado' => OrdenProduccion::ESTADO_CANCELADA,
                    'requiere_calidad' => false,
                ]);
            }

            return $orden;
        }

        return OrdenProduccion::query()->create([
            'tipo_producto_id' => $productoId,
            'user_id' => Auth::id() ?? User::query()->value('id'),
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 1,
            'unidad_medida_id' => $unidadId,
            'estado' => OrdenProduccion::ESTADO_CANCELADA,
            'notas' => self::BOM_TEMPLATE_NOTE,
            'prioridad' => 'Normal',
            'requiere_calidad' => false,
            'etapas_totales' => 0,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ]);
    }

    private function mapOrdenForView(OrdenProduccion $orden): object
    {
        $estado = match (mb_strtolower((string) $orden->estado)) {
            'en proceso' => 'EN_PROCESO',
            'completada' => 'FINALIZADA',
            'finalizada' => 'FINALIZADA',
            'cancelada' => 'CANCELADA',
            default => 'PENDIENTE',
        };

        $cantidad = (float) $orden->cantidad_produccion;
        $cantidadCompletada = round($cantidad * ((float) $orden->porcentaje_completado / 100), 4);

        $usosMaterial = $orden->consumosMateriales
            ->map(fn (ConsumoMaterial $uso): object => (object) [
                'material' => (object) ['nombre' => $uso->insumo?->nombre],
                'cantidad_usada' => (float) $uso->cantidad_consumida,
                'cantidad_merma' => (float) $uso->cantidad_desperdicio,
            ]);

        $lineasMateriales = $orden->materiales->isNotEmpty()
            ? $orden->materiales
            : $this->obtenerLineasBomPorProducto((int) $orden->tipo_producto_id);

        $materialesPlanificados = $lineasMateriales
            ->filter(fn (OrdenProduccionMaterial $linea): bool => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado')
            ->values()
            ->map(fn (OrdenProduccionMaterial $linea): object => (object) [
                'id' => (int) $linea->insumo_id,
                'nombre' => $linea->insumo?->nombre,
                'cantidad_planificada' => (float) $linea->cantidad_necesaria,
                'cantidad_consumida' => (float) $linea->cantidad_utilizada,
                'cantidad_merma' => (float) $linea->cantidad_desperdicio,
            ]);

        $etapaPendienteAprobacion = $orden->trazabilidadEtapas
            ->first(fn ($etapa): bool => in_array($etapa->estado, ['Esperando Aprobacion', 'Esperando Aprobación'], true));

        $bloqueadaAprobacion = $etapaPendienteAprobacion !== null;

        $nombreEtapaPendiente = $etapaPendienteAprobacion
            ? ($etapaPendienteAprobacion->etapaPlantilla?->nombre ?? ('Etapa #' . $etapaPendienteAprobacion->numero_secuencia))
            : null;

        return (object) [
            'id' => $orden->id,
            'producto' => (object) [
                'nombre' => $orden->tipoProducto?->nombre,
                'sku' => $orden->tipoProducto?->slug,
            ],
            'cantidad' => $cantidad,
            'cantidad_completada' => $cantidadCompletada,
            'estado' => (object) ['nombre' => $estado],
            'etapa_fabricacion_actual' => (string) ($orden->etapa_fabricacion_actual ?: 'Corte'),
            'responsable' => (object) [
                'id' => $orden->user?->id,
                'nombre' => $orden->user?->name,
            ],
            'maquina_asignada' => $orden->maquina_asignada,
            'turno_asignado' => $orden->turno_asignado,
            'fecha_inicio' => $orden->fecha_inicio_prevista,
            'fecha_esperada' => $orden->fecha_fin_prevista,
            'materiales_ids' => $materialesPlanificados->pluck('id')->values()->all(),
            'materialesPlanificados' => $materialesPlanificados,
            'usosMaterial' => $usosMaterial,
            'merma_total' => round((float) $orden->consumosMateriales->sum('cantidad_desperdicio'), 4),
            'merma_porcentaje' => $this->calcularMermaPorcentajeOrden($orden),
            'bloqueada_aprobacion' => $bloqueadaAprobacion,
            'etapa_pendiente_aprobacion' => $nombreEtapaPendiente,
        ];
    }

    private function obtenerLineasBomPorProducto(int $productoId): Collection
    {
        if (isset($this->lineasBomCachePorProducto[$productoId])) {
            return $this->lineasBomCachePorProducto[$productoId];
        }

        $lineas = OrdenProduccionMaterial::query()
            ->with('insumo:id,nombre')
            ->whereHas('ordenProduccion', function ($query) use ($productoId): void {
                $query->where('tipo_producto_id', $productoId)
                    ->whereIn('notas', [self::BOM_TEMPLATE_NOTE, self::BOM_TEMPLATE_LEGACY_NOTE]);
            })
            ->orderBy('numero_linea')
            ->get();

        $this->lineasBomCachePorProducto[$productoId] = $lineas;

        return $lineas;
    }

    private function sincronizarMaterialesDesdeBom(OrdenProduccion $orden): void
    {
        if ($orden->materiales()->exists()) {
            return;
        }

        $lineasPlantilla = $this->obtenerLineasBomPorProducto((int) $orden->tipo_producto_id)
            ->filter(fn (OrdenProduccionMaterial $linea): bool => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado')
            ->values();

        if ($lineasPlantilla->isEmpty()) {
            return;
        }

        $factorCantidad = max(1.0, (float) $orden->cantidad_produccion);

        foreach ($lineasPlantilla as $index => $lineaPlantilla) {
            OrdenProduccionMaterial::query()->create([
                'orden_produccion_id' => $orden->id,
                'insumo_id' => (int) $lineaPlantilla->insumo_id,
                'unidad_medida_id' => (int) $lineaPlantilla->unidad_medida_id,
                'cantidad_necesaria' => round((float) $lineaPlantilla->cantidad_necesaria * $factorCantidad, 4),
                'cantidad_utilizada' => 0,
                'cantidad_desperdicio' => 0,
                'estado_asignacion' => 'Asignado',
                'notas_asignacion' => 'Asignado desde receta BOM al crear orden operativa.',
                'numero_linea' => $index + 1,
            ]);
        }
    }

    private function obtenerLoteDisponibleParaConsumo(int $insumoId): ?LoteInsumo
    {
        $baseQuery = LoteInsumo::query()
            ->where('insumo_id', $insumoId)
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', 'Rechazado');
            });

        // Prioriza lotes activos, y si no existen usa lotes legacy con stock.
        $lote = (clone $baseQuery)
            ->where('activo', true)
            ->orderBy('fecha_recepcion')
            ->orderBy('id')
            ->first();

        if ($lote) {
            return $lote;
        }

        return (clone $baseQuery)
            ->orderByDesc('activo')
            ->orderBy('fecha_recepcion')
            ->orderBy('id')
            ->first();
    }

    /**
     * Regulariza el desfase entre stock global de insumo y stock acumulado en lotes.
     *
     * @param Collection<int, Insumo> $insumos
     */
    private function regularizarDesfaseLotes(Collection $insumos): void
    {
        if ($insumos->isEmpty()) {
            return;
        }

        $insumos = $insumos->filter(fn ($insumo): bool => $insumo instanceof Insumo)->values();

        if ($insumos->isEmpty()) {
            return;
        }

        $insumoIds = $insumos->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $stockPorLotes = LoteInsumo::query()
            ->selectRaw('insumo_id, SUM(cantidad_en_stock) as stock_lotes')
            ->whereIn('insumo_id', $insumoIds)
            ->whereRaw('cantidad_en_stock > 0')
            ->where(function ($query): void {
                $query->whereNull('estado_calidad')
                    ->orWhere('estado_calidad', '!=', 'Rechazado');
            })
            ->groupBy('insumo_id')
            ->pluck('stock_lotes', 'insumo_id');

        $ubicacionActivaId = UbicacionAlmacen::query()->where('activo', true)->value('id');

        foreach ($insumos as $insumo) {
            $stockGlobal = round((float) $insumo->stock_actual, 4);
            $stockLotes = round((float) ($stockPorLotes[$insumo->id] ?? 0), 4);
            $desfase = round($stockGlobal - $stockLotes, 4);

            if ($desfase <= 0.0001) {
                continue;
            }

            $ordenCompraAjuste = $this->resolverOrdenCompraAjuste($insumo);

            if (! $ordenCompraAjuste) {
                continue;
            }

            $ubicacionId = $insumo->ubicacion_almacen_id ?: $ubicacionActivaId;

            if (! $ubicacionId) {
                continue;
            }

            LoteInsumo::query()->create([
                'numero_lote' => sprintf('AJ-%d-%s', (int) $insumo->id, now()->format('YmdHisu')),
                'insumo_id' => (int) $insumo->id,
                'orden_compra_id' => (int) $ordenCompraAjuste->id,
                'proveedor_id' => (int) $ordenCompraAjuste->proveedor_id,
                'fecha_lote' => now()->toDateString(),
                'fecha_recepcion' => now(),
                'cantidad_recibida' => $desfase,
                'cantidad_en_stock' => $desfase,
                'cantidad_consumida' => 0,
                'cantidad_rechazada' => 0,
                'ubicacion_almacen_id' => $ubicacionId,
                'estado_calidad' => 'Aceptado',
                'user_recepcion_id' => Auth::id(),
                'notas' => 'Lote de ajuste generado automáticamente para alinear stock global vs lotes.',
                'activo' => true,
            ]);
        }
    }

    private function resolverOrdenCompraAjuste(Insumo $insumo): ?OrdenCompra
    {
        $ordenCompraDesdeLote = LoteInsumo::query()
            ->where('insumo_id', (int) $insumo->id)
            ->whereNotNull('orden_compra_id')
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeLote) {
            $orden = OrdenCompra::query()->find((int) $ordenCompraDesdeLote);
            if ($orden) {
                return $orden;
            }
        }

        $ordenCompraDesdeDetalle = DB::table('ordenes_compra_detalles')
            ->where('insumo_id', (int) $insumo->id)
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeDetalle) {
            $orden = OrdenCompra::query()->find((int) $ordenCompraDesdeDetalle);
            if ($orden) {
                return $orden;
            }
        }

        $proveedorId = (int) ($insumo->proveedor_id ?: 0);

        if ($proveedorId <= 0) {
            $proveedorId = (int) (DB::table('proveedores')->orderBy('id')->value('id') ?: 0);
        }

        if ($proveedorId <= 0) {
            return null;
        }

        $notaTecnica = 'Orden técnica para ajuste automático de lotes.';
        $ordenTecnica = OrdenCompra::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'Recibida')
            ->where('notas', $notaTecnica)
            ->first();

        if ($ordenTecnica) {
            return $ordenTecnica;
        }

        $userId = (int) (Auth::id() ?: User::query()->value('id'));

        if ($userId <= 0) {
            return null;
        }

        return OrdenCompra::query()->create([
            'proveedor_id' => $proveedorId,
            'user_id' => $userId,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Recibida',
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
            'notas' => $notaTecnica,
        ]);
    }

    private function ordenBloqueadaPorAprobacion(OrdenProduccion $orden): bool
    {
        return $orden->trazabilidadEtapas()
            ->whereIn('estado', ['Esperando Aprobacion', 'Esperando Aprobación'])
            ->exists();
    }

    private function calcularMermaPorcentajeOrden(OrdenProduccion $orden): float
    {
        $desperdicio = (float) $orden->consumosMateriales->sum('cantidad_desperdicio');
        $consumo = (float) $orden->consumosMateriales->sum('cantidad_consumida');
        $total = $desperdicio + $consumo;

        if ($total <= 0) {
            return 0;
        }

        return round(($desperdicio / $total) * 100, 2);
    }

    private function construirStepperEtapas(OrdenProduccion $orden, object $ordenView): Collection
    {
        $etapasTraza = $orden->trazabilidadEtapas
            ->sortBy('numero_secuencia')
            ->values();

        if ($etapasTraza->isNotEmpty()) {
            return $etapasTraza->map(function ($etapa, int $index): object {
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
                ];
            });
        }

        $etapaActual = (string) ($ordenView->etapa_fabricacion_actual ?? 'Corte');
        $posicionActual = array_search($etapaActual, self::ETAPAS_FABRICACION, true);
        $posicionActual = $posicionActual === false ? 0 : $posicionActual;

        return collect(self::ETAPAS_FABRICACION)
            ->values()
            ->map(function (string $nombreEtapa, int $index) use ($posicionActual): object {
                $estadoUi = $index < $posicionActual
                    ? 'finalizada'
                    : ($index === $posicionActual ? 'actual' : 'pendiente');

                $estado = match ($estadoUi) {
                    'finalizada' => 'Finalizada',
                    'actual' => 'En Proceso',
                    default => 'Pendiente',
                };

                return (object) [
                    'numero' => $index + 1,
                    'nombre' => $nombreEtapa,
                    'estado' => $estado,
                    'estado_ui' => $estadoUi,
                ];
            });
    }
}
