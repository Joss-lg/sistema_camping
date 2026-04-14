<?php

namespace App\Console\Commands;

use App\Models\ReporteGenerado;
use Illuminate\Console\Command;

class ExpireGeneratedReportsCommand extends Command
{
    protected $signature = 'reportes:expirar
        {--cleanup-days=30 : Days after expiration before soft-delete}
        {--dry-run : Show counts without applying changes}';

    protected $description = 'Marks expired generated reports and soft-deletes old expired records';

    public function handle(): int
    {
        $cleanupDays = max(1, (int) $this->option('cleanup-days'));
        $dryRun = (bool) $this->option('dry-run');

        $baseExpireQuery = ReporteGenerado::query()
            ->whereNull('deleted_at')
            ->whereNotNull('expiracion_at')
            ->where('expiracion_at', '<=', now())
            ->whereIn('estado', ['Generado', 'Descargado']);

        $toExpire = (clone $baseExpireQuery)->count();

        if (! $dryRun && $toExpire > 0) {
            (clone $baseExpireQuery)->update([
                'estado' => 'Expirado',
                'updated_at' => now(),
            ]);
        }

        $baseCleanupQuery = ReporteGenerado::query()
            ->whereNull('deleted_at')
            ->where('estado', 'Expirado')
            ->whereNotNull('expiracion_at')
            ->where('expiracion_at', '<=', now()->subDays($cleanupDays));

        $toCleanup = (clone $baseCleanupQuery)->count();

        if (! $dryRun && $toCleanup > 0) {
            (clone $baseCleanupQuery)->delete();
        }

        $this->info('Reporte de expiracion de reportes generados');
        $this->line('Pendientes por expirar: ' . $toExpire);
        $this->line('Pendientes por limpiar: ' . $toCleanup);

        if ($dryRun) {
            $this->warn('Dry run activo: no se aplicaron cambios.');
        }

        return self::SUCCESS;
    }
}
