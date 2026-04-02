<?php

namespace App\Listeners;

use App\Events\OrdenProduccionCreada;
use App\Models\EtapaProduccionPlantilla;
use App\Models\OrdenProduccion;
use App\Models\TrazabilidadEtapa;
use Illuminate\Support\Collection;

class GenerarEtapasTrazabilidad
{
    public function handle(OrdenProduccionCreada $event): void
    {
        $orden = $event->ordenProduccion;

        // Verificar si ya existen etapas para esta orden
        $yaExisten = $orden->trazabilidadEtapas()->exists();
        if ($yaExisten) {
            return;
        }

        // Obtener plantillas del tipo de producto
        $plantillas = $this->obtenerPlantillasParaOrden($orden);

        if ($plantillas->isEmpty()) {
            return;
        }

        // Crear etapas de trazabilidad desde plantillas
        foreach ($plantillas as $plantilla) {
            TrazabilidadEtapa::create([
                'orden_produccion_id' => $orden->id,
                'etapa_plantilla_id' => $plantilla->id,
                'numero_secuencia' => $plantilla->numero_secuencia,
                'numero_ejecucion' => 1,
                'fecha_inicio_prevista' => now(),
                'fecha_fin_prevista' => now()->addMinutes((int) $plantilla->duracion_estimada_minutos),
                'responsable_id' => null,
                'estado' => 'Pendiente',
            ]);
        }

        // Actualizar contador de etapas en la orden
        $orden->forceFill([
            'etapas_totales' => $plantillas->count(),
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ])->save();
    }

    /**
     * Obtener plantillas de etapas para una orden de producción
     */
    private function obtenerPlantillasParaOrden(OrdenProduccion $orden): Collection
    {
        $plantillas = EtapaProduccionPlantilla::query()
            ->where('tipo_producto_id', $orden->tipo_producto_id)
            ->where('activo', true)
            ->orderBy('numero_secuencia')
            ->get();

        if ($plantillas->isEmpty()) {
            return collect();
        }

        return $plantillas;
    }
}