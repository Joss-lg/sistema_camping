<?php

namespace App\Http\Controllers;

use App\Models\ConsumoMaterial;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
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

        OrdenProduccion::query()->create([
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
        $lote = LoteInsumo::query()
            ->where('insumo_id', $material->id)
            ->where('activo', true)
            ->whereRaw('cantidad_en_stock > 0')
            ->orderBy('fecha_recepcion')
            ->first();

        if (! $lote) {
            return back()->withErrors(['material_id' => 'No hay lote disponible con stock para el material seleccionado.'])->withInput();
        }

        $cantidadUsada = (float) $data['cantidad_usada'];
        $cantidadMerma = (float) ($data['cantidad_merma'] ?? 0);
        $total = $cantidadUsada + $cantidadMerma;

        if ($cantidadMerma > 0 && trim((string) ($data['motivo_merma'] ?? '')) === '') {
            return back()->withErrors(['motivo_merma' => 'Debes capturar motivo de merma cuando exista desperdicio.'])->withInput();
        }

        if ($total > (float) $material->stock_actual) {
            return back()->withErrors(['cantidad_usada' => 'El consumo total excede el stock actual del material.'])->withInput();
        }

        if ($total > (float) $lote->cantidadDisponible()) {
            return back()->withErrors(['cantidad_usada' => 'El consumo total excede la disponibilidad del lote.'])->withInput();
        }

        DB::transaction(function () use ($orden, $material, $lote, $cantidadUsada, $cantidadMerma, $total, $data): void {
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
        });

        return redirect()->route('produccion.index')->with('ok', 'Consumo de material registrado correctamente.');
    }

    public function updateEstado(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validate([
            'estado' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADA'],
            'cantidad_completada' => ['nullable', 'numeric', 'gte:0'],
            'etapa_fabricacion_actual' => ['nullable', 'in:Corte,Costura,Ensamblado,Acabado'],
        ]);

        $orden = OrdenProduccion::query()->findOrFail($id);

        if ($this->ordenBloqueadaPorAprobacion($orden)) {
            return back()->withErrors(['estado' => 'No se puede iniciar/cambiar estado mientras exista una etapa en Esperando Aprobacion.']);
        }

        $estado = match ($data['estado']) {
            'EN_PROCESO' => OrdenProduccion::ESTADO_EN_PROCESO,
            'FINALIZADA' => OrdenProduccion::ESTADO_FINALIZADA,
            default => OrdenProduccion::ESTADO_PENDIENTE,
        };

        $cantidadCompletada = (float) ($data['cantidad_completada'] ?? 0);
        $porcentaje = (float) ($orden->cantidad_produccion > 0
            ? min(100, max(0, ($cantidadCompletada / (float) $orden->cantidad_produccion) * 100))
            : 0);

        $orden->estado = $estado;
        $orden->porcentaje_completado = $porcentaje;
        if (! empty($data['etapa_fabricacion_actual'])) {
            $orden->etapa_fabricacion_actual = (string) $data['etapa_fabricacion_actual'];
        }

        if ($estado === OrdenProduccion::ESTADO_EN_PROCESO && ! $orden->fecha_inicio_real) {
            $orden->fecha_inicio_real = now()->toDateString();
        }

        if (OrdenProduccion::esEstadoFinalizado($estado)) {
            $orden->porcentaje_completado = 100;
            $orden->fecha_fin_real = now()->toDateString();
            $orden->etapas_completadas = $orden->etapas_totales;
            $orden->etapa_fabricacion_actual = 'Acabado';
        }

        $orden->save();

        return back()->with('ok', 'Estado de la orden actualizado correctamente.');
    }

    public function updateAsignacion(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        $data = $request->validate([
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'maquina_asignada' => ['nullable', 'string', 'max:120'],
            'turno_asignado' => ['nullable', 'in:Manana,Tarde,Noche'],
        ]);

        $orden = OrdenProduccion::query()->findOrFail($id);

        DB::transaction(function () use ($orden, $data): void {
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
        });

        return back()->with('ok', 'Asignación de tarea actualizada correctamente.');
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
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get()
            ->map(function (OrdenProduccionMaterial $linea): object {
                return (object) [
                    'id' => $linea->id,
                    'producto' => (object) [
                        'nombre' => $linea->ordenProduccion?->tipoProducto?->nombre,
                        'sku' => $linea->ordenProduccion?->tipoProducto?->slug,
                    ],
                    'material' => (object) [
                        'nombre' => $linea->insumo?->nombre,
                    ],
                    'cantidad_base' => (float) $linea->cantidad_necesaria,
                    'activo' => mb_strtolower((string) $linea->estado_asignacion) !== 'cancelado',
                ];
            });

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
            $ordenBase = OrdenProduccion::query()
                ->where('tipo_producto_id', (int) $data['producto_id'])
                ->whereIn('estado', ['Pendiente', 'En Proceso'])
                ->orderByDesc('id')
                ->first();

            if (! $ordenBase) {
                $ordenBase = OrdenProduccion::query()->create([
                    'tipo_producto_id' => (int) $data['producto_id'],
                    'user_id' => Auth::id() ?? User::query()->value('id'),
                    'fecha_orden' => now(),
                    'fecha_inicio_prevista' => now()->toDateString(),
                    'fecha_fin_prevista' => now()->addDay()->toDateString(),
                    'cantidad_produccion' => 1,
                    'unidad_medida_id' => (int) $unidadId,
                    'estado' => 'Pendiente',
                    'notas' => 'Orden base generada para gestión BOM.',
                    'prioridad' => 'Normal',
                    'requiere_calidad' => true,
                    'etapas_totales' => 0,
                    'etapas_completadas' => 0,
                    'porcentaje_completado' => 0,
                ]);
            }

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
            ->with([
                'tipoProducto:id,nombre,slug',
                'user:id,name',
                'consumosMateriales.insumo:id,nombre',
                'trazabilidadEtapas:id,orden_produccion_id,etapa_plantilla_id,numero_secuencia,estado',
                'trazabilidadEtapas.etapaPlantilla:id,nombre',
            ])
            ->orderByDesc('updated_at')
            ->limit(150);
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
            'usosMaterial' => $usosMaterial,
            'merma_total' => round((float) $orden->consumosMateriales->sum('cantidad_desperdicio'), 4),
            'merma_porcentaje' => $this->calcularMermaPorcentajeOrden($orden),
            'bloqueada_aprobacion' => $bloqueadaAprobacion,
            'etapa_pendiente_aprobacion' => $nombreEtapaPendiente,
        ];
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
}
