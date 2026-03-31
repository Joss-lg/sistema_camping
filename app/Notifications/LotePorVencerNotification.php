<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class LotePorVencerNotification extends Notification
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
        $loteId = (int) ($this->payload['lote_id'] ?? 0);
        $numeroLote = (string) ($this->payload['numero_lote'] ?? 'N/A');
        $insumoId = (int) ($this->payload['insumo_id'] ?? 0);
        $insumoNombre = (string) ($this->payload['insumo_nombre'] ?? 'Insumo');
        $insumoCodigo = (string) ($this->payload['insumo_codigo'] ?? 'N/A');
        $categoria = (string) ($this->payload['categoria_insumo'] ?? 'General');
        $proveedor = (string) ($this->payload['proveedor'] ?? 'Sin proveedor');
        $fechaVencimiento = (string) ($this->payload['fecha_vencimiento'] ?? now()->toDateString());
        $stock = (float) ($this->payload['cantidad_en_stock'] ?? 0);
        $estadoCalidad = (string) ($this->payload['estado_calidad'] ?? 'Pendiente');
        $diasRestantes = (int) ($this->payload['dias_restantes'] ?? 0);

        return [
            'tipo' => 'Alerta',
            'categoria' => 'Vencimiento',
            'titulo' => 'Lote de insumo por vencer',
            'mensaje' => sprintf(
                'El lote %s del insumo %s (%s) vence en %d dias (%s). Stock: %.4f. Categoria: %s. Proveedor: %s. Estado de calidad: %s.',
                $numeroLote,
                $insumoNombre,
                $insumoCodigo,
                $diasRestantes,
                $fechaVencimiento,
                $stock,
                $categoria,
                $proveedor,
                $estadoCalidad
            ),
            'url_accion' => $this->resolverUrlAccion($insumoId),
            'icono' => 'flask-alert',
            'lote_id' => $loteId,
            'numero_lote' => $numeroLote,
            'insumo_id' => $insumoId,
            'fecha_vencimiento' => $fechaVencimiento,
            'dias_restantes' => $diasRestantes,
            'cantidad_en_stock' => $stock,
        ];
    }

    private function resolverUrlAccion(int $insumoId): string
    {
        if ($insumoId > 0 && Route::has('insumos.show')) {
            return route('insumos.show', $insumoId);
        }

        if (Route::has('insumos.index')) {
            return route('insumos.index');
        }

        return '/insumos';
    }
}
