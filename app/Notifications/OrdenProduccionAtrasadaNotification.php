<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class OrdenProduccionAtrasadaNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private readonly array $payload)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $ordenId = (int) ($this->payload['orden_id'] ?? 0);
        $numeroOrden = (string) ($this->payload['numero_orden'] ?? 'N/A');
        $estado = (string) ($this->payload['estado'] ?? 'Pendiente');
        $fechaFinPrevista = (string) ($this->payload['fecha_fin_prevista'] ?? now()->toDateString());
        $tipoProducto = (string) ($this->payload['tipo_producto'] ?? 'Sin tipo');
        $responsable = (string) ($this->payload['responsable'] ?? 'Sin responsable');
        $avance = (float) ($this->payload['porcentaje_completado'] ?? 0);
        $prioridad = (string) ($this->payload['prioridad'] ?? 'Normal');

        return [
            'tipo' => 'Alerta',
            'categoria' => 'Produccion',
            'titulo' => 'Orden de produccion atrasada',
            'mensaje' => sprintf(
                'La orden %s (%s) debio finalizar el %s y sigue en estado %s. Avance: %.2f%%. Responsable: %s. Prioridad: %s.',
                $numeroOrden,
                $tipoProducto,
                $fechaFinPrevista,
                $estado,
                $avance,
                $responsable,
                $prioridad
            ),
            'url_accion' => $this->resolverUrlAccion($ordenId),
            'icono' => 'clock-alert',
            'orden_id' => $ordenId,
            'numero_orden' => $numeroOrden,
            'estado' => $estado,
            'fecha_fin_prevista' => $fechaFinPrevista,
            'porcentaje_completado' => $avance,
            'prioridad' => $prioridad,
        ];
    }

    private function resolverUrlAccion(int $ordenId): string
    {
        if ($ordenId > 0 && Route::has('ordenes-produccion.show')) {
            return route('ordenes-produccion.show', $ordenId);
        }

        if (Route::has('ordenes-produccion.index')) {
            return route('ordenes-produccion.index');
        }

        return '/ordenes-produccion';
    }
}
