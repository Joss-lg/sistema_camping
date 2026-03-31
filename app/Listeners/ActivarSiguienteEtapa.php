<?php

namespace App\Listeners;

use App\Events\EtapaCompletada;
use App\Models\NotificacionSistema;
use App\Models\OrdenProduccion;
use App\Models\User;
use App\Models\TrazabilidadEtapa;
use Illuminate\Support\Facades\DB;

class ActivarSiguienteEtapa
{
    public function handle(EtapaCompletada $event): void
    {
        DB::transaction(function () use ($event): void {
            $etapaCompletada = TrazabilidadEtapa::query()
                ->lockForUpdate()
                ->find($event->etapa->id);

            if (! $etapaCompletada) {
                return;
            }

            $orden = OrdenProduccion::query()
                ->lockForUpdate()
                ->find($etapaCompletada->orden_produccion_id);

            if (! $orden) {
                return;
            }

            $etapasCompletadas = $orden->trazabilidadEtapas()
                ->where('estado', 'Finalizada')
                ->count();

            $orden->etapas_completadas = $etapasCompletadas;
            $orden->porcentaje_completado = $orden->etapas_totales > 0
                ? (($etapasCompletadas / $orden->etapas_totales) * 100)
                : 0;
            $orden->save();

            $siguienteEtapa = $orden->trazabilidadEtapas()
                ->where('estado', 'Pendiente')
                ->where('numero_secuencia', '>', $etapaCompletada->numero_secuencia)
                ->orderBy('numero_secuencia')
                ->first();

            if ($siguienteEtapa) {
                if ($orden->estado === 'Pendiente') {
                    $orden->marcarEnProceso();
                }

                $siguienteEtapa->estado = 'Esperando Aprobación';
                $siguienteEtapa->fecha_aprobacion = null;
                $siguienteEtapa->aprobado_por = null;
                $siguienteEtapa->save();

                $destinatario = $siguienteEtapa->responsableArea ?: $this->buscarEncargadoArea($siguienteEtapa);

                NotificacionSistema::create([
                    'titulo' => 'Aprobacion pendiente de etapa',
                    'mensaje' => sprintf(
                        'La etapa "%s" de la orden %s esta esperando aprobacion manual.',
                        $siguienteEtapa->etapaPlantilla?->nombre ?? ('#' . $siguienteEtapa->etapa_plantilla_id),
                        $orden->numero_orden ?? ('#' . $orden->id)
                    ),
                    'tipo' => 'Informativa',
                    'modulo' => 'Trazabilidad',
                    'prioridad' => 'Alta',
                    'user_id' => $destinatario?->id,
                    'role_id' => $destinatario?->role_id,
                    'estado' => 'Pendiente',
                    'fecha_programada' => now(),
                    'requiere_accion' => true,
                    'url_accion' => '/trazabilidad',
                    'metadata' => [
                        'orden_produccion_id' => $orden->id,
                        'trazabilidad_etapa_id' => $siguienteEtapa->id,
                        'origen' => 'listener.activar_siguiente_etapa',
                    ],
                ]);
                return;
            }

            if ($orden->etapas_totales > 0 && $etapasCompletadas >= $orden->etapas_totales) {
                $orden->marcarCompletada();
            }
        });
    }

    private function buscarEncargadoArea(TrazabilidadEtapa $etapa): ?User
    {
        $area = mb_strtolower((string) ($etapa->etapaPlantilla?->tipo_etapa ?: $etapa->etapaPlantilla?->nombre ?: ''));

        if ($area === '') {
            return null;
        }

        return User::query()
            ->where('activo', true)
            ->where(function ($query) use ($area): void {
                $query->whereRaw('LOWER(departamento) like ?', ['%' . $area . '%'])
                    ->orWhereHas('role', function ($roleQuery) use ($area): void {
                        $roleQuery->whereRaw('LOWER(slug) like ?', ['%' . $area . '%'])
                            ->orWhereRaw('LOWER(nombre) like ?', ['%' . $area . '%']);
                    });
            })
            ->orderBy('id')
            ->first();
    }
}
