<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConsumoMaterial;
use App\Models\EtapaProduccionPlantilla;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use App\Models\ProductoTerminado;
use App\Models\TrazabilidadEtapa;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait BuildsProduccionViewData
{
    /**
     * @return array<int, string>
     */
    protected function etapasFabricacionDefault(): array
    {
        return ['Corte', 'Costura', 'Ensamblado', 'Acabado'];
    }

    protected function canManage(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $rol = PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre ?: ''));

        return in_array($rol, ['ADMINISTRADOR', 'ENCARGADO'], true)
            || $user->canCustom('Produccion', 'crear');
    }

    protected function buildOrdenesQuery()
    {
        return OrdenProduccion::query()
            ->operativas()
            ->with([
                'tipoProducto:id,nombre,slug',
                'user:id,name',
                'materiales.insumo:id,nombre',
                'consumosMateriales.insumo:id,nombre',
                'consumosMateriales.user:id,name',
                'trazabilidadEtapas:id,orden_produccion_id,etapa_plantilla_id,numero_secuencia,estado',
                'trazabilidadEtapas.etapaPlantilla:id,nombre',
                'productosTerminados:id,orden_produccion_id,estado_calidad,fecha_inspeccion,created_at',
            ])
            ->orderByDesc('updated_at')
            ->limit(150);
    }

    protected function mapOrdenForView(OrdenProduccion $orden): object
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

        $estadoCalidad = $this->obtenerEstadoCalidadActual($orden);
        $ordenFinalizada = OrdenProduccion::esEstadoFinalizado((string) $orden->estado);
        $bloqueadaCalidad = $ordenFinalizada && in_array((string) $estadoCalidad, [
            ProductoTerminado::ESTADO_CALIDAD_PENDIENTE,
            ProductoTerminado::ESTADO_CALIDAD_ACEPTADA,
        ], true);

        $nombreEtapaPendiente = $etapaPendienteAprobacion
            ? ($etapaPendienteAprobacion->etapaPlantilla?->nombre ?? ('Etapa #' . $etapaPendienteAprobacion->numero_secuencia))
            : null;

        $motivoBloqueoEdicion = null;
        if ($bloqueadaCalidad) {
            $motivoBloqueoEdicion = $estadoCalidad === ProductoTerminado::ESTADO_CALIDAD_ACEPTADA
                ? 'Orden aprobada por calidad. Gestión deshabilitada definitivamente.'
                : 'Orden finalizada en espera de inspección de calidad. Gestión deshabilitada hasta resolver la inspección.';
        } elseif ($bloqueadaAprobacion) {
            $motivoBloqueoEdicion = 'Orden bloqueada por aprobación pendiente de etapa.';
        }

        $edicionBloqueada = $bloqueadaAprobacion || $bloqueadaCalidad;

        return (object) [
            'id' => $orden->id,
            'producto' => (object) [
                'nombre' => $orden->tipoProducto?->nombre,
                'sku' => $orden->tipoProducto?->slug,
            ],
            'cantidad' => $cantidad,
            'cantidad_completada' => $cantidadCompletada,
            'estado' => (object) ['nombre' => $estado],
            'etapa_fabricacion_actual' => (string) ($orden->etapa_fabricacion_actual ?: $this->obtenerEtapaInicialPorProducto((int) $orden->tipo_producto_id)),
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
            'bloqueada_calidad' => $bloqueadaCalidad,
            'estado_calidad' => $estadoCalidad,
            'edicion_bloqueada' => $edicionBloqueada,
            'motivo_bloqueo_edicion' => $motivoBloqueoEdicion,
            'etapa_pendiente_aprobacion' => $nombreEtapaPendiente,
        ];
    }

    protected function ordenBloqueadaPorAprobacion(OrdenProduccion $orden): bool
    {
        return $orden->trazabilidadEtapas()
            ->whereIn('estado', ['Esperando Aprobacion', 'Esperando Aprobación'])
            ->exists();
    }

    protected function ordenBloqueadaPorCalidad(OrdenProduccion $orden): bool
    {
        if (! OrdenProduccion::esEstadoFinalizado((string) $orden->estado)) {
            return false;
        }

        $estadoCalidad = $this->obtenerEstadoCalidadActual($orden);

        return in_array((string) $estadoCalidad, [
            ProductoTerminado::ESTADO_CALIDAD_PENDIENTE,
            ProductoTerminado::ESTADO_CALIDAD_ACEPTADA,
        ], true);
    }

    protected function obtenerEstadoCalidadActual(OrdenProduccion $orden): ?string
    {
        if ($orden->relationLoaded('productosTerminados') && $orden->productosTerminados->isNotEmpty()) {
            return (string) $orden->productosTerminados
                ->sortByDesc(function (ProductoTerminado $producto): int {
                    return (int) (($producto->fecha_inspeccion?->getTimestamp() ?? 0) ?: ($producto->created_at?->getTimestamp() ?? 0));
                })
                ->first()?->estado_calidad;
        }

        return ProductoTerminado::query()
            ->where('orden_produccion_id', $orden->id)
            ->orderByDesc('fecha_inspeccion')
            ->orderByDesc('id')
            ->value('estado_calidad');
    }

    protected function mensajeBloqueoCalidad(OrdenProduccion $orden): string
    {
        $estadoCalidad = $this->obtenerEstadoCalidadActual($orden);

        if ($estadoCalidad === ProductoTerminado::ESTADO_CALIDAD_ACEPTADA) {
            return 'La orden ya fue aprobada por calidad y quedó cerrada. No se permiten más ediciones.';
        }

        return 'La orden ya finalizó producción y está pendiente de inspección de calidad. No se permiten ediciones hasta inspeccionar el producto.';
    }

    protected function calcularMermaPorcentajeOrden(OrdenProduccion $orden): float
    {
        $desperdicio = (float) $orden->consumosMateriales->sum('cantidad_desperdicio');
        $consumo = (float) $orden->consumosMateriales->sum('cantidad_consumida');
        $total = $desperdicio + $consumo;

        if ($total <= 0) {
            return 0;
        }

        return round(($desperdicio / $total) * 100, 2);
    }

    protected function construirStepperEtapas(OrdenProduccion $orden, object $ordenView): Collection
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
                    str_contains($estadoNormalizado, 'completada') => 'finalizada',
                    str_contains($estadoNormalizado, 'aprobado') => 'finalizada',
                    str_contains($estadoNormalizado, 'aceptada') => 'finalizada',
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

        $etapasBase = $this->obtenerEtapasBaseParaOrden($orden);

        $etapaActual = $this->normalizarNombreEtapa((string) ($ordenView->etapa_fabricacion_actual ?? ($etapasBase[0] ?? 'Corte')));
        $posicionActual = collect($etapasBase)
            ->search(fn (string $etapa): bool => $this->normalizarNombreEtapa($etapa) === $etapaActual);
        $posicionActual = $posicionActual === false ? 0 : $posicionActual;
        $ordenFinalizada = ($ordenView->estado->nombre ?? '') === 'FINALIZADA';

        return collect($etapasBase)
            ->values()
            ->map(function (string $nombreEtapa, int $index) use ($posicionActual, $ordenFinalizada): object {
                if ($ordenFinalizada) {
                    $estadoUi = 'finalizada';
                } else {
                    $estadoUi = $index < $posicionActual
                        ? 'finalizada'
                        : ($index === $posicionActual ? 'actual' : 'pendiente');
                }

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

    /**
     * @return array<int, string>
     */
    protected function obtenerEtapasBaseParaOrden(OrdenProduccion $orden): array
    {
        $etapasDesdePlantilla = EtapaProduccionPlantilla::query()
            ->where('tipo_producto_id', $orden->tipo_producto_id)
            ->where('activo', true)
            ->orderBy('numero_secuencia')
            ->pluck('nombre')
            ->filter(fn ($nombre): bool => trim((string) $nombre) !== '')
            ->values()
            ->all();

        if (! empty($etapasDesdePlantilla)) {
            return array_map(fn ($nombre): string => (string) $nombre, $etapasDesdePlantilla);
        }

        return $this->etapasFabricacionDefault();
    }

    protected function obtenerEtapaInicialPorProducto(int $productoId): string
    {
        $ordenReferencia = new OrdenProduccion();
        $ordenReferencia->tipo_producto_id = $productoId;

        return $this->obtenerEtapasBaseParaOrden($ordenReferencia)[0] ?? $this->etapasFabricacionDefault()[0];
    }

    protected function obtenerEtapaFinalPorOrden(OrdenProduccion $orden): string
    {
        $etapas = $this->obtenerEtapasBaseParaOrden($orden);

        $etapasDefault = $this->etapasFabricacionDefault();

        return $etapas[count($etapas) - 1] ?? $etapasDefault[array_key_last($etapasDefault)];
    }

    protected function normalizarNombreEtapa(string $nombre): string
    {
        $nombre = trim(mb_strtolower($nombre));
        $mapa = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
        ];

        return strtr($nombre, $mapa);
    }
}
