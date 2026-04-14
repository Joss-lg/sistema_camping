<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OpsHealthCheckCommand extends Command
{
    protected $signature = 'ops:health {--strict : Return non-zero exit code when warnings are found}';

    protected $description = 'Checks scheduler, queue, and failure backlog for operational readiness';

    public function handle(): int
    {
        $warnings = [];
        $errors = [];

        $requiredSchedules = [
            'verificar-stock-bajo-terminados',
            'generar-ordenes-compra-automaticas',
            'verificar-ordenes-atrasadas-diario',
            'verificar-vencimiento-lotes-diario',
            'calcular-costos-promedio-semanal',
            'expirar-y-limpiar-reportes-generados',
        ];

        $registered = collect(app(Schedule::class)->events())
            ->map(function ($event): string {
                $name = trim((string) ($event->description ?? ''));

                if ($name !== '') {
                    return $name;
                }

                return trim((string) $event->getSummaryForDisplay());
            })
            ->filter()
            ->values()
            ->all();

        $missingSchedules = array_values(array_diff($requiredSchedules, $registered));

        if (! empty($missingSchedules)) {
            $errors[] = 'Missing scheduled jobs: '.implode(', ', $missingSchedules);
        }

        $queueDriver = (string) config('queue.default', 'sync');
        if ($queueDriver === 'sync') {
            $warnings[] = 'Queue driver is sync. Use database/redis in non-test environments.';
        }

        if (! Schema::hasTable('jobs')) {
            $errors[] = 'jobs table is missing. Run queue migrations.';
        } else {
            $pendingJobs = DB::table('jobs')->count();
            if ($pendingJobs > 500) {
                $warnings[] = "High queue backlog detected ({$pendingJobs} pending jobs).";
            }
        }

        if (! Schema::hasTable('failed_jobs')) {
            $warnings[] = 'failed_jobs table is missing. Failed jobs will not be persisted.';
        } else {
            $failedLastDay = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            if ($failedLastDay > 0) {
                $warnings[] = "Detected {$failedLastDay} failed jobs in the last 24h.";
            }
        }

        $this->info('Operational health check');

        if (empty($errors) && empty($warnings)) {
            $this->line('Status: OK');
            return self::SUCCESS;
        }

        foreach ($errors as $error) {
            $this->error("ERROR: {$error}");
        }

        foreach ($warnings as $warning) {
            $this->warn("WARN: {$warning}");
        }

        if (! empty($errors)) {
            $this->line('Status: FAIL');
            return self::FAILURE;
        }

        $this->line('Status: WARN');

        if ($this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
