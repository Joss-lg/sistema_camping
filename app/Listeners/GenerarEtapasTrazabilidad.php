<?php

namespace App\Listeners;

use App\Events\OrdenProduccionCreada;
use App\Models\EtapaProduccionPlantilla;
use App\Models\TrazabilidadEtapa;
use Illuminate\Support\Facades\DB;

class GenerarEtapasTrazabilidad
{
    public function handle(OrdenProduccionCreada $event): void
    {
        $orden = $event->ordenProduccion;

        DB::transaction(function () use ($orden): void {
            $plantillas = EtapaProduccionPlantilla::query()
                ->where('tipo_producto_id', $orden->tipo_producto_id)
                ->where('activo', true)
                ->orderBy('numero_secuencia')
                ->get();

            if ($plantillas->isEmpty()) {
                return;
            }

            $yaExisten = $orden->trazabilidadEtapas()->exists();
            if ($yaExisten) {
                return;
            }

            foreach ($plantillas as $plantilla) {
                TrazabilidadEtapa::create([
                    'orden_produccion_id' => $orden->id,
                    'etapa_plantilla_id' => $plantilla->id,
                    'numero_secuencia' => $plantilla->numero_secuencia,
                    'numero_ejecucion' => 1,
                    'fecha_inicio_prevista' => now(),
                    'fecha_fin_prevista' => now()->addMinutes((int) $plantilla->duracion_estimada_minutos),
                    'duracion_estimada_minutos' => $plantilla->duracion_estimada_minutos,
                    'cantidad_operarios' => $plantilla->cantidad_operarios,
                    'estado' => 'Pendiente',
                ]);
            }

            $orden->forceFill([
                'etapas_totales' => $plantillas->count(),
                'etapas_completadas' => 0,
                'porcentaje_completado' => 0,
            ])->save();
        });
    }
}
