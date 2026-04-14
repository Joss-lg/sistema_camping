<?php

namespace App\Services;

use App\Models\OrdenProduccion;
use App\Models\TrazabilidadEtapa;
use Illuminate\Support\Facades\DB;

class ProduccionSeguimientoService
{
    public function __construct(
        private readonly ProduccionInventarioService $inventarioService
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function actualizar(OrdenProduccion $orden, array $data, array $context): void
    {
        $bloqueadaAprobacion = (bool) ($context['bloqueadaAprobacion'] ?? false);
        $etapaFinal = (string) ($context['etapaFinal'] ?? 'Acabado');
        $etapaInicial = (string) ($context['etapaInicial'] ?? 'Corte');
        $etapasPermitidas = (array) ($context['etapasPermitidas'] ?? []);

        DB::transaction(function () use ($orden, $data, $bloqueadaAprobacion, $etapaFinal, $etapaInicial, $etapasPermitidas): void {
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

            $etapaSeleccionada = (string) ($data['etapa_fabricacion_actual']
                ?? $orden->etapa_fabricacion_actual
                ?? $etapaInicial);

            $indiceEtapaSeleccionada = collect($etapasPermitidas)
                ->search(fn (string $etapa): bool => $this->normalizarNombreEtapa($etapa) === $this->normalizarNombreEtapa($etapaSeleccionada));
            $indiceEtapaSeleccionada = $indiceEtapaSeleccionada === false ? 0 : (int) $indiceEtapaSeleccionada;
            $esUltimaEtapa = $indiceEtapaSeleccionada >= (count($etapasPermitidas) - 1);
            $totalEtapasReferencia = (int) ($orden->etapas_totales > 0 ? $orden->etapas_totales : count($etapasPermitidas));
            $totalEtapasReferencia = max(1, $totalEtapasReferencia);

            if (OrdenProduccion::esEstadoFinalizado($estado)) {
                $etapasTrazabilidad = $orden->trazabilidadEtapas()
                    ->with('etapaPlantilla:id,nombre')
                    ->orderBy('numero_secuencia')
                    ->get();

                if ($etapasTrazabilidad->isNotEmpty()) {
                    $etapaObjetivo = $etapasTrazabilidad->first(function (TrazabilidadEtapa $etapa) use ($etapaSeleccionada): bool {
                        return $this->normalizarNombreEtapa((string) ($etapa->etapaPlantilla?->nombre ?? '')) === $this->normalizarNombreEtapa($etapaSeleccionada)
                            && ! in_array((string) $etapa->estado, ['Finalizada', 'Completada'], true);
                    });

                    $etapaObjetivo ??= $etapasTrazabilidad->first(function (TrazabilidadEtapa $etapa): bool {
                        return in_array((string) $etapa->estado, ['Pendiente', 'En Proceso', 'Esperando Aprobacion', 'Esperando Aprobación'], true);
                    });

                    if ($etapaObjetivo && ! in_array((string) $etapaObjetivo->estado, ['Finalizada', 'Completada'], true)) {
                        $etapaObjetivo->completar();
                    }

                    $orden->refresh();

                    if (! OrdenProduccion::esEstadoFinalizado((string) $orden->estado)) {
                        $orden->estado = OrdenProduccion::ESTADO_EN_PROCESO;
                        $orden->etapa_fabricacion_actual = $etapaSeleccionada;
                        $orden->save();
                    }

                    return;
                }

                if (! $esUltimaEtapa) {
                    $orden->estado = OrdenProduccion::ESTADO_EN_PROCESO;
                    $orden->etapas_completadas = max((int) $orden->etapas_completadas, $indiceEtapaSeleccionada + 1);
                    $orden->porcentaje_completado = round(($orden->etapas_completadas / $totalEtapasReferencia) * 100, 2);
                    $orden->etapa_fabricacion_actual = (string) ($etapasPermitidas[$indiceEtapaSeleccionada + 1] ?? $etapaSeleccionada);
                    $orden->save();

                    return;
                }

                $orden->etapa_fabricacion_actual = $etapaFinal;
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
                $orden->etapa_fabricacion_actual = $orden->etapa_fabricacion_actual ?: $etapaInicial;
                $this->inventarioService->ocultarTerminadosDeOrdenReabierta($orden->id);
            }

            $orden->save();
        });
    }

    private function normalizarNombreEtapa(string $etapa): string
    {
        $etapa = trim(mb_strtolower($etapa));

        return str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $etapa);
    }
}
