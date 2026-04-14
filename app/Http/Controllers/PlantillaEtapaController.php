<?php

namespace App\Http\Controllers;

use App\Models\EtapaProduccionPlantilla;
use App\Models\TipoProducto;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlantillaEtapaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule(Auth::user(), 'Produccion'), 403);

        $canManage = $this->canManage();

        $productos = TipoProducto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug'])
            ->map(fn (TipoProducto $producto): object => (object) [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->slug,
            ]);

        $plantillas = EtapaProduccionPlantilla::query()
            ->with('tipoProducto:id,nombre,slug')
            ->where('activo', true)
            ->orderBy('tipo_producto_id')
            ->orderBy('numero_secuencia')
            ->get()
            ->groupBy('tipo_producto_id')
            ->map(fn (Collection $etapas): object => (object) [
                'producto' => (object) [
                    'id' => $etapas->first()?->tipoProducto?->id,
                    'nombre' => $etapas->first()?->tipoProducto?->nombre,
                    'sku' => $etapas->first()?->tipoProducto?->slug,
                ],
                'etapas' => $etapas->map(fn (EtapaProduccionPlantilla $etapa): object => (object) [
                    'id' => $etapa->id,
                    'nombre' => $etapa->nombre,
                    'codigo' => $etapa->codigo,
                    'numero_secuencia' => (int) $etapa->numero_secuencia,
                    'tipo_etapa' => $etapa->tipo_etapa,
                    'duracion_estimada_minutos' => (int) $etapa->duracion_estimada_minutos,
                    'cantidad_operarios' => (int) $etapa->cantidad_operarios,
                    'requiere_validacion' => (bool) $etapa->requiere_validacion,
                    'es_etapa_critica' => (bool) $etapa->es_etapa_critica,
                    'descripcion' => $etapa->descripcion,
                    'instrucciones_detalladas' => $etapa->instrucciones_detalladas,
                ])->values(),
            ])
            ->values();

        $productoEditarId = (int) $request->query('editar_producto', 0);
        $etapasProductoEditar = collect();

        if ($productoEditarId > 0) {
            $etapasProductoEditar = EtapaProduccionPlantilla::query()
                ->where('tipo_producto_id', $productoEditarId)
                ->where('activo', true)
                ->orderBy('numero_secuencia')
                ->get();
        }

        $prefillEtapas = $etapasProductoEditar->isNotEmpty()
            ? $etapasProductoEditar->map(fn (EtapaProduccionPlantilla $etapa): array => [
                'id' => (int) $etapa->id,
                'nombre' => (string) $etapa->nombre,
                'numero_secuencia' => (int) $etapa->numero_secuencia,
                'duracion_estimada_minutos' => (int) $etapa->duracion_estimada_minutos,
                'cantidad_operarios' => (int) $etapa->cantidad_operarios,
                'tipo_etapa' => (string) ($etapa->tipo_etapa ?: 'Manufactura'),
                'descripcion' => (string) ($etapa->descripcion ?? ''),
                'instrucciones_detalladas' => (string) ($etapa->instrucciones_detalladas ?? ''),
                'requiere_validacion' => $etapa->requiere_validacion ? 1 : 0,
                'es_etapa_critica' => $etapa->es_etapa_critica ? 1 : 0,
            ])->values()->all()
            : [[
                'id' => null,
                'nombre' => old('nombre'),
                'numero_secuencia' => old('numero_secuencia', 1),
                'duracion_estimada_minutos' => old('duracion_estimada_minutos', 30),
                'cantidad_operarios' => old('cantidad_operarios', 1),
                'tipo_etapa' => old('tipo_etapa', 'Manufactura'),
                'descripcion' => old('descripcion'),
                'instrucciones_detalladas' => old('instrucciones_detalladas'),
                'requiere_validacion' => old('requiere_validacion') ? 1 : 0,
                'es_etapa_critica' => old('es_etapa_critica') ? 1 : 0,
            ]];

        $modoActualizacion = $productoEditarId > 0 && $etapasProductoEditar->isNotEmpty();

        return view('produccion.plantillas', compact(
            'canManage',
            'productos',
            'plantillas',
            'prefillEtapas',
            'modoActualizacion'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Produccion', 'editar'), 403);

        if ($request->filled('etapas')) {
            $data = $request->validate([
                'producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
                'modo_actualizacion' => ['nullable', 'boolean'],
                'etapas' => ['required', 'array', 'min:1'],
                'etapas.*.id' => ['nullable', 'integer', 'exists:etapas_produccion_plantilla,id'],
                'etapas.*.nombre' => ['required', 'string', 'max:100'],
                'etapas.*.numero_secuencia' => ['required', 'integer', 'min:1', 'max:999'],
                'etapas.*.duracion_estimada_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
                'etapas.*.cantidad_operarios' => ['nullable', 'integer', 'min:1', 'max:100'],
                'etapas.*.tipo_etapa' => ['nullable', 'string', 'max:50'],
                'etapas.*.descripcion' => ['nullable', 'string'],
                'etapas.*.instrucciones_detalladas' => ['nullable', 'string'],
                'etapas.*.requiere_validacion' => ['nullable', 'boolean'],
                'etapas.*.es_etapa_critica' => ['nullable', 'boolean'],
            ]);

            $secuencias = collect($data['etapas'])
                ->map(fn (array $etapa): int => (int) $etapa['numero_secuencia'])
                ->filter(fn (int $secuencia): bool => $secuencia > 0)
                ->values();

            $duplicadas = $secuencias
                ->countBy()
                ->filter(fn (int $cantidad): bool => $cantidad > 1)
                ->keys()
                ->values()
                ->all();

            if (! empty($duplicadas)) {
                return back()
                    ->withErrors([
                        'etapas' => 'No se puede guardar porque hay secuencias duplicadas: ' . implode(', ', $duplicadas) . '.',
                    ])
                    ->withInput();
            }

            $idsEnRequest = collect($data['etapas'])
                ->map(fn (array $etapa): int => (int) ($etapa['id'] ?? 0))
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $queryConflictos = EtapaProduccionPlantilla::query()
                ->where('tipo_producto_id', (int) $data['producto_id'])
                ->where('activo', true)
                ->whereIn('numero_secuencia', $secuencias->all());

            if (! empty($idsEnRequest)) {
                $queryConflictos->whereNotIn('id', $idsEnRequest);
            }

            $secuenciasEnConflicto = $queryConflictos
                ->pluck('numero_secuencia')
                ->unique()
                ->values()
                ->all();

            if (! empty($secuenciasEnConflicto)) {
                return back()
                    ->withErrors([
                        'etapas' => 'Ya existen etapas activas con estas secuencias para el producto: ' . implode(', ', $secuenciasEnConflicto) . '.',
                    ])
                    ->withInput();
            }

            $modoActualizacion = (bool) ($data['modo_actualizacion'] ?? false);

            DB::transaction(function () use ($data, $modoActualizacion): void {
                // En modo actualización, desactivar etapas que no vienen en el request
                if ($modoActualizacion) {
                    $idsEnRequest = collect($data['etapas'])
                        ->map(fn (array $etapa): int => (int) ($etapa['id'] ?? 0))
                        ->filter(fn (int $id): bool => $id > 0)
                        ->unique()
                        ->values()
                        ->all();

                    if (! empty($idsEnRequest)) {
                        EtapaProduccionPlantilla::query()
                            ->where('tipo_producto_id', (int) $data['producto_id'])
                            ->where('activo', true)
                            ->whereNotIn('id', $idsEnRequest)
                            ->update(['activo' => false]);
                    } else {
                        // Compatibilidad para requests antiguos sin id en etapas.
                        $secuenciasEnRequest = collect($data['etapas'])
                            ->map(fn (array $etapa): int => (int) $etapa['numero_secuencia'])
                            ->unique()
                            ->values()
                            ->all();

                        EtapaProduccionPlantilla::query()
                            ->where('tipo_producto_id', (int) $data['producto_id'])
                            ->whereNotIn('numero_secuencia', $secuenciasEnRequest)
                            ->where('activo', true)
                            ->update(['activo' => false]);
                    }
                }

                foreach ($data['etapas'] as $etapaData) {
                    $this->guardarEtapaPlantilla((int) $data['producto_id'], (array) $etapaData, $modoActualizacion);
                }
            });

            return redirect()->route('produccion.plantillas.index')
                ->with('ok', 'Etapas de plantilla guardadas y disponibles para nuevas órdenes en trazabilidad.');
        }

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:tipos_producto,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'numero_secuencia' => ['required', 'integer', 'min:1', 'max:999'],
            'duracion_estimada_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
            'cantidad_operarios' => ['nullable', 'integer', 'min:1', 'max:100'],
            'tipo_etapa' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'instrucciones_detalladas' => ['nullable', 'string'],
            'requiere_validacion' => ['nullable', 'boolean'],
            'es_etapa_critica' => ['nullable', 'boolean'],
        ]);

        $yaExisteSecuenciaActiva = EtapaProduccionPlantilla::query()
            ->where('tipo_producto_id', (int) $data['producto_id'])
            ->where('numero_secuencia', (int) $data['numero_secuencia'])
            ->where('activo', true)
            ->exists();

        if ($yaExisteSecuenciaActiva) {
            return back()
                ->withErrors([
                    'numero_secuencia' => 'La secuencia ya existe para este producto. Usa otro número.',
                ])
                ->withInput();
        }

        $this->guardarEtapaPlantilla((int) $data['producto_id'], $data, false);

        return redirect()->route('produccion.plantillas.index')
            ->with('ok', 'Etapa de plantilla guardada y disponible para nuevas órdenes en trazabilidad.');
    }

    /**
     * @param array<string, mixed> $etapaData
     */
    private function guardarEtapaPlantilla(int $productoId, array $etapaData, bool $modoActualizacion): void
    {
        $existente = null;

        if ($modoActualizacion) {
            $etapaId = (int) ($etapaData['id'] ?? 0);

            if ($etapaId > 0) {
                $existente = EtapaProduccionPlantilla::withTrashed()
                    ->where('id', $etapaId)
                    ->where('tipo_producto_id', $productoId)
                    ->first();
            }

            if (! $existente) {
                $existente = EtapaProduccionPlantilla::withTrashed()
                    ->where('tipo_producto_id', $productoId)
                    ->where('numero_secuencia', (int) $etapaData['numero_secuencia'])
                    ->orderByDesc('id')
                    ->first();
            }
        }

        if ($existente) {
            if (method_exists($existente, 'trashed') && $existente->trashed()) {
                $existente->restore();
            }

            $existente->update([
                'nombre' => (string) $etapaData['nombre'],
                'descripcion' => $etapaData['descripcion'] ?? null,
                'duracion_estimada_minutos' => (int) $etapaData['duracion_estimada_minutos'],
                'cantidad_operarios' => (int) ($etapaData['cantidad_operarios'] ?? 1),
                'instrucciones_detalladas' => $etapaData['instrucciones_detalladas'] ?? null,
                'requiere_validacion' => (bool) ($etapaData['requiere_validacion'] ?? false),
                'es_etapa_critica' => (bool) ($etapaData['es_etapa_critica'] ?? false),
                'activo' => true,
                'tipo_etapa' => (string) ($etapaData['tipo_etapa'] ?? 'Manufactura'),
            ]);

            return;
        }

        $codigoBase = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', (string) $etapaData['nombre']) ?: 'ETAPA');
        $codigo = sprintf('TPL-%d-%03d-%s', $productoId, (int) $etapaData['numero_secuencia'], substr($codigoBase, 0, 20));

        $contador = 1;
        $codigoFinal = $codigo;
        while (EtapaProduccionPlantilla::query()->where('codigo', $codigoFinal)->exists()) {
            $contador++;
            $codigoFinal = $codigo . '-' . $contador;
        }

        EtapaProduccionPlantilla::query()->create([
            'nombre' => (string) $etapaData['nombre'],
            'descripcion' => $etapaData['descripcion'] ?? null,
            'codigo' => $codigoFinal,
            'tipo_producto_id' => $productoId,
            'numero_secuencia' => (int) $etapaData['numero_secuencia'],
            'duracion_estimada_minutos' => (int) $etapaData['duracion_estimada_minutos'],
            'cantidad_operarios' => (int) ($etapaData['cantidad_operarios'] ?? 1),
            'instrucciones_detalladas' => $etapaData['instrucciones_detalladas'] ?? null,
            'requiere_validacion' => (bool) ($etapaData['requiere_validacion'] ?? false),
            'es_etapa_critica' => (bool) ($etapaData['es_etapa_critica'] ?? false),
            'activo' => true,
            'tipo_etapa' => (string) ($etapaData['tipo_etapa'] ?? 'Manufactura'),
        ]);
    }

    private function canManage(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $rol = PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre ?: ''));

        return in_array($rol, ['SUPER_ADMIN', 'SUPERVISOR_ALMACEN', 'ALMACEN', 'ADMIN'], true)
            || $user->canCustom('Produccion', 'crear');
    }
}
