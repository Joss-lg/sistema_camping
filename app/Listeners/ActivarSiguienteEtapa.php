<?php

namespace App\Listeners;

use App\Events\EtapaCompletada;
use App\Models\OrdenProduccion;
use App\Models\User;
use App\Models\TrazabilidadEtapa;
use App\Services\NotificacionSistemaPatternService;
use App\Services\PermisoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivarSiguienteEtapa
{
    public function __construct(
        private readonly NotificacionSistemaPatternService $notificacionService
    ) {
    }

    public function handle(EtapaCompletada $event): void
    {
        try {
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

                    $destinatarios = $this->notificacionService->usuariosActivos();

                    if ($destinatarios->isNotEmpty()) {
                        foreach ($destinatarios as $usuario) {
                            $this->notificacionService->crearSiNoExisteHoy([
                                'titulo' => 'Aprobacion pendiente de etapa',
                                'mensaje' => sprintf(
                                    'La etapa "%s" de la orden %s esta esperando aprobacion manual.',
                                    $siguienteEtapa->etapaPlantilla?->nombre ?? ('#' . $siguienteEtapa->etapa_plantilla_id),
                                    $orden->numero_orden ?? ('#' . $orden->id)
                                ),
                                'tipo' => 'Informativa',
                                'modulo' => 'Trazabilidad',
                                'prioridad' => 'Alta',
                                'user_id' => (int) $usuario->id,
                                'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                                'estado' => 'Pendiente',
                                'fecha_programada' => now(),
                                'requiere_accion' => true,
                                'url_accion' => '/trazabilidad',
                                'metadata' => [
                                    'orden_produccion_id' => $orden->id,
                                    'trazabilidad_etapa_id' => $siguienteEtapa->id,
                                    'origen' => 'listener.activar_siguiente_etapa',
                                ],
                            ], 'trazabilidad_etapa_id', (int) $siguienteEtapa->id);
                        }
                    } else {
                        $fallbackRoleId = PermisoService::resolveRoleByInput('SUPER_ADMIN')?->id
                            ?: PermisoService::resolveRoleByInput('ADMIN')?->id;

                        if (! $fallbackRoleId) {
                            $fallbackRoleId = \App\Models\Role::query()->orderBy('id')->value('id');
                        }

                        if ($fallbackRoleId) {
                            $this->notificacionService->crearSiNoExisteHoy([
                                'titulo' => 'Aprobacion pendiente de etapa',
                                'mensaje' => sprintf(
                                    'La etapa "%s" de la orden %s esta esperando aprobacion manual.',
                                    $siguienteEtapa->etapaPlantilla?->nombre ?? ('#' . $siguienteEtapa->etapa_plantilla_id),
                                    $orden->numero_orden ?? ('#' . $orden->id)
                                ),
                                'tipo' => 'Informativa',
                                'modulo' => 'Trazabilidad',
                                'prioridad' => 'Alta',
                                'user_id' => null,
                                'role_id' => (int) $fallbackRoleId,
                                'estado' => 'Pendiente',
                                'fecha_programada' => now(),
                                'requiere_accion' => true,
                                'url_accion' => '/trazabilidad',
                                'metadata' => [
                                    'orden_produccion_id' => $orden->id,
                                    'trazabilidad_etapa_id' => $siguienteEtapa->id,
                                    'origen' => 'listener.activar_siguiente_etapa',
                                ],
                            ], 'trazabilidad_etapa_id', (int) $siguienteEtapa->id);
                        }
                    }

                    return;
                }

                if ($orden->etapas_totales > 0 && $etapasCompletadas >= $orden->etapas_totales) {
                    $orden->marcarCompletada();
                }
            });
        } catch (\Throwable $e) {
            Log::error('Listener ActivarSiguienteEtapa fallo', [
                'listener' => self::class,
                'evento' => EtapaCompletada::class,
                'etapa_id' => (int) ($event->etapa->id ?? 0),
                'mensaje' => $e->getMessage(),
            ]);

            throw $e;
        }
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
