<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsProduccionViewData;
use App\Http\Controllers\Concerns\HandlesProduccionInventory;
use App\Http\Requests\RegistrarConsumoRequest;
use App\Http\Requests\StoreBomRequest;
use App\Http\Requests\StoreProduccionRequest;
use App\Http\Requests\UpdateSeguimientoRequest;
use App\Events\OrdenProduccionCreada;
use App\Models\ConsumoMaterial;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Services\PermisoService;
use App\Services\ProduccionConsumoService;
use App\Services\ProduccionSeguimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProduccionController extends Controller
{
    use BuildsProduccionViewData;
    use HandlesProduccionInventory;

    protected const BOM_TEMPLATE_NOTE = 'Plantilla BOM (no ejecutar)';
    protected const BOM_TEMPLATE_LEGACY_NOTE = 'Orden base generada para gestión BOM.';

    /** @var array<int, string> */
    protected const ETAPAS_FABRICACION = ['Corte', 'Costura', 'Ensamblado', 'Acabado'];

    /** @var array<int, string> */
    protected const TURNOS = ['Manana', 'Tarde', 'Noche'];

    /** @var array<int, string> */
    protected const TIPOS_MERMA = ['Corte', 'Costura', 'Defecto', 'Manejo', 'Otro'];

    public function __construct(
        protected readonly ProduccionConsumoService $consumoService,
        protected readonly ProduccionSeguimientoService $seguimientoService
    ) {
    }

    /**
     * Renderiza el tablero operativo de producción.
     *
     * Proceso involucrado: planificación diaria y monitoreo de órdenes,
     * responsables, materiales y métricas de merma para toma de decisiones.
     */
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
            ->selectRaw('SUM(cantidad_desperdicio) as total_desperdicio, SUM(cantidad_consumida + cantidad_desperdicio) as total_consumido')
            ->first();

        $statsMerma = round((float) ($consumosPeriodo->total_desperdicio ?? 0), 2);
        $totalConsumidoPeriodo = (float) ($consumosPeriodo->total_consumido ?? 0);
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

    /**
     * Crea una orden de producción operativa.
     *
     * Proceso involucrado: alta de orden, asignación de etapa inicial y
     * sincronización de receta BOM para habilitar seguimiento y consumo.
     */
    public function store(StoreProduccionRequest $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validated();

        $unidadId = UnidadMedida::query()->where('activo', true)->value('id')
            ?? UnidadMedida::query()->value('id');

        if (! $unidadId) {
            return back()->withErrors(['producto_id' => 'No existe una unidad de medida activa para crear la orden.'])->withInput();
        }

        $etapaInicial = $this->obtenerEtapaInicialPorProducto((int) $data['producto_id']);

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => (int) $data['producto_id'],
            'user_id' => (int) $data['responsable_id'],
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => $data['fecha_inicio'] ? Carbon::parse($data['fecha_inicio'])->toDateString() : now()->toDateString(),
            'fecha_fin_prevista' => $data['fecha_esperada'] ? Carbon::parse($data['fecha_esperada'])->toDateString() : now()->addDay()->toDateString(),
            'cantidad_produccion' => (float) $data['cantidad'],
            'unidad_medida_id' => (int) $unidadId,
            'estado' => OrdenProduccion::ESTADO_PENDIENTE,
            'etapa_fabricacion_actual' => $etapaInicial,
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

    /**
     * Registra consumo real de material en una orden de producción.
     *
     * Proceso involucrado: control de inventario por lote, validación de merma,
     * trazabilidad de uso y actualización de líneas de materiales de la orden.
     */
    public function registrarConsumo(RegistrarConsumoRequest $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validated();

        $orden = OrdenProduccion::query()->findOrFail((int) $data['orden_produccion_id']);

        if ($this->ordenBloqueadaPorCalidad($orden)) {
            return back()->withErrors([
                'orden_produccion_id' => $this->mensajeBloqueoCalidad($orden),
            ])->withInput();
        }

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

        $this->consumoService->registrarConsumo(
            $orden,
            $material,
            $lineaMaterial,
            $lote,
            $cantidadUsada,
            $cantidadMerma,
            $total,
            $data['tipo_merma'] ?? null,
            $data['motivo_merma'] ?? null
        );

        if ($request->boolean('redirect_seguimiento')) {
            return redirect()
                ->route('produccion.seguimiento', ['id' => $orden->id])
                ->with('ok', 'Consumo de material registrado correctamente.');
        }

        return redirect()->route('produccion.index')->with('ok', 'Consumo de material registrado correctamente.');
    }

    /**
     * Actualiza el avance operativo y la etapa de una orden en seguimiento.
     *
     * Proceso involucrado: gestión de estado de fabricación, bloqueo por calidad
     * o aprobación pendiente y consistencia de la línea de tiempo de etapas.
     */
    public function updateSeguimiento(UpdateSeguimientoRequest $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $orden = OrdenProduccion::query()->findOrFail($id);

        if ($this->ordenBloqueadaPorCalidad($orden)) {
            return back()->with('error', $this->mensajeBloqueoCalidad($orden));
        }

        $etapasPermitidas = $this->obtenerEtapasBaseParaOrden($orden);

        $data = $request->validated();
        $bloqueadaAprobacion = $this->ordenBloqueadaPorAprobacion($orden);
        $etapaFinal = $this->obtenerEtapaFinalPorOrden($orden);
        $etapaInicial = $this->obtenerEtapaInicialPorProducto((int) $orden->tipo_producto_id);

        $this->seguimientoService->actualizar($orden, $data, [
            'bloqueadaAprobacion' => $bloqueadaAprobacion,
            'etapaFinal' => $etapaFinal,
            'etapaInicial' => $etapaInicial,
            'etapasPermitidas' => $etapasPermitidas,
        ]);

        if ($bloqueadaAprobacion) {
            return back()->with('ok', 'Asignación actualizada. El estado no se modificó porque la orden está bloqueada por aprobación pendiente.');
        }

        return back()->with('ok', 'Seguimiento actualizado correctamente.');
    }

    /**
     * Cancela una orden de producción activa.
     *
     * Proceso involucrado: cierre administrativo de una orden para detener
     * su ejecución operativa en planta.
     */
    public function cancelar(int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule(request()->user(), 'Produccion', 'editar'), 403);

        $orden = OrdenProduccion::query()
            ->with(['consumosMateriales.insumo', 'consumosMateriales.loteInsumo'])
            ->findOrFail($id);

        DB::transaction(function () use ($orden): void {
            // Revertir consumos registrados: devolver stock al insumo y al lote
            foreach ($orden->consumosMateriales as $consumo) {
                $totalRevertir = (float) $consumo->cantidad_consumida + (float) $consumo->cantidad_desperdicio;

                if ($totalRevertir > 0) {
                    $insumo = $consumo->insumo;
                    if ($insumo) {
                        $insumo->stock_actual = (float) $insumo->stock_actual + $totalRevertir;
                        $insumo->save();
                    }

                    $lote = $consumo->loteInsumo;
                    if ($lote) {
                        $lote->cantidad_en_stock = (float) $lote->cantidad_en_stock + $totalRevertir;
                        $lote->cantidad_consumida = max(0, (float) $lote->cantidad_consumida - $totalRevertir);
                        $lote->save();
                    }
                }

                $consumo->delete();
            }

            // Marcar todas las líneas de materiales planificados como cancelado
            $orden->materiales()
                ->where('estado_asignacion', '!=', 'cancelado')
                ->update(['estado_asignacion' => 'cancelado']);

            $orden->estado = OrdenProduccion::ESTADO_CANCELADA;
            $orden->save();
        });

        return redirect()->route('produccion.index')->with('ok', 'Orden cancelada. Los materiales consumidos fueron devueltos al stock.');
    }

    /**
     * Retorna la tabla parcial de órdenes filtradas.
     *
     * Proceso involucrado: consulta segmentada por responsable para operación
     * diaria del tablero sin recargar la vista completa.
     */
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

    /**
     * Muestra el detalle de seguimiento de una orden específica.
     *
     * Proceso involucrado: control de ejecución por etapas, historial de
     * consumos, análisis de merma y disponibilidad de materiales por lote.
     */
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
        $etapasFabricacionOrden = $this->obtenerEtapasBaseParaOrden($orden);

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

        $consumosFiltroBase = ConsumoMaterial::query()
            ->with(['insumo:id,nombre', 'user:id,name'])
            ->where('orden_produccion_id', $orden->id)
            ->get(['id', 'insumo_id', 'user_id']);

        $materialesFiltro = $consumosFiltroBase
            ->pluck('insumo')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values()
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
            ]);

        $usuariosFiltro = $consumosFiltroBase
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
                    ->orWhere('estado_calidad', '!=', LoteInsumo::ESTADO_CALIDAD_RECHAZADO);
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

        $puedeRegistrarConsumo = ! $ordenView->edicion_bloqueada
            && ! empty($ordenView->materiales_ids)
            && $materialesConsumo->isNotEmpty();

        $tiposMerma = self::TIPOS_MERMA;

        return view('produccion.seguimiento', compact(
            'canManage',
            'ordenView',
            'usuarios',
            'stepperEtapas',
            'etapasFabricacionOrden',
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

    /**
     * Muestra la gestión de recetas BOM por tipo de producto.
     *
     * Proceso involucrado: mantenimiento de la receta técnica base que luego
     * se replica en órdenes operativas para consumo y seguimiento.
     */
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
                $query->where('es_plantilla_bom', true);
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

    /**
     * Guarda/actualiza líneas de receta BOM para un producto.
     *
     * Proceso involucrado: estandarización de insumos requeridos por producto,
     * versionado operativo de la plantilla y alineación de órdenes futuras.
     */
    public function bomStore(StoreBomRequest $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validated();

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

}
 