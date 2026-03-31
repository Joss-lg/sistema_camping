<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class StockBajoNotification extends Notification
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
        $nombre = (string) ($this->payload['nombre'] ?? 'Insumo');
        $codigo = (string) ($this->payload['codigo_insumo'] ?? 'N/A');
        $stockActual = (float) ($this->payload['stock_actual'] ?? 0);
        $stockMinimo = (float) ($this->payload['stock_minimo'] ?? 0);
        $unidad = (string) ($this->payload['unidad_medida'] ?? 'u.');
        $tipoProducto = (string) ($this->payload['tipo_producto'] ?? 'General');
        $insumoId = (int) ($this->payload['insumo_id'] ?? 0);

        return [
            'tipo' => 'Alerta',
            'categoria' => 'Inventario',
            'titulo' => 'Stock bajo detectado',
            'mensaje' => sprintf(
                'El insumo %s (%s) esta por debajo del minimo: %.4f %s / minimo %.4f %s. Tipo de producto: %s.',
                $nombre,
                $codigo,
                $stockActual,
                $unidad,
                $stockMinimo,
                $unidad,
                $tipoProducto
            ),
            'url_accion' => $this->resolverUrlAccion($insumoId),
            'icono' => 'alert-triangle',
            'insumo_id' => $insumoId,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo,
            'unidad_medida' => $unidad,
        ];
    }

    private function resolverUrlAccion(int $insumoId): string
    {
        if ($insumoId > 0 && Route::has('insumos.show')) {
            return route('insumos.show', $insumoId);
        }

        if (Route::has('insumos.bajo-stock')) {
            return route('insumos.bajo-stock');
        }

        if (Route::has('insumos.index')) {
            return route('insumos.index');
        }

        return '/insumos';
    }
}
