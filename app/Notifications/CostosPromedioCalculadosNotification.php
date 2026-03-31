<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CostosPromedioCalculadosNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public array $payload;

    public function __construct($cantidadActualizada)
    {
        $this->payload = [
            'cantidad_actualizada' => $cantidadActualizada,
            'fecha_calculo' => now()->format('d/m/Y H:i'),
            'dias_considerados' => 90,
        ];
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
        $cantidad = $this->payload['cantidad_actualizada'];
        $fecha = $this->payload['fecha_calculo'];
        $dias = $this->payload['dias_considerados'];

        return [
            'tipo' => 'Informacion',
            'categoria' => 'Costos',
            'titulo' => 'Cálculo de costos promedio completado',
            'mensaje' => "Se ha recalculado el costo promedio para {$cantidad} insumo(s) basado en las compras de los últimos {$dias} días. Cálculo realizado el {$fecha}.",
            'url_accion' => route('insumos.index', [], false) ?? '/insumos',
            'icono' => 'calculator',
            'cantidad_actualizada' => $cantidad,
            'fecha_calculo' => $this->payload['fecha_calculo'],
        ];
    }
}
