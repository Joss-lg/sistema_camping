<?php

namespace App\Console\Commands;

use App\Services\AbastecimientoAutomaticoService;
use Illuminate\Console\Command;

class GenerarOrdenesCompraAutomaticas extends Command
{
    protected $signature = 'abastecimiento:generar-ordenes {--dry-run : Simula sin crear registros}';

    protected $description = 'Genera ordenes de compra automaticas para insumos con stock bajo';

    public function handle(AbastecimientoAutomaticoService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $resultado = $service->generarOrdenes($dryRun);

        $this->line('');
        $this->info($dryRun ? 'Simulacion de abastecimiento automatico' : 'Abastecimiento automatico ejecutado');
        $this->line('Insumos detectados: ' . (int) ($resultado['insumos_detectados'] ?? 0));
        $this->line('Ordenes creadas: ' . (int) ($resultado['ordenes_creadas'] ?? 0));
        $this->line('Detalles creados: ' . (int) ($resultado['detalles_creados'] ?? 0));
        $this->line('Insumos omitidos por orden abierta: ' . (int) ($resultado['insumos_omitidos_por_orden_abierta'] ?? 0));
        $this->line('Proveedores omitidos: ' . (int) ($resultado['proveedores_omitidos'] ?? 0));

        if (! empty($resultado['error'])) {
            $this->error((string) $resultado['error']);
            return self::FAILURE;
        }

        if (! empty($resultado['ordenes']) && is_array($resultado['ordenes'])) {
            foreach ($resultado['ordenes'] as $orden) {
                $this->line('- ' . ($orden['numero_orden'] ?? 'N/A') . ' | ' . ($orden['proveedor'] ?? 'Proveedor') . ' | items: ' . count($orden['insumos'] ?? []));
            }
        }

        return self::SUCCESS;
    }
}
