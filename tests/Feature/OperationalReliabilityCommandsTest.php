<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReliabilityCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_health_command_runs_successfully_in_nominal_state(): void
    {
        $this->artisan('ops:health')
            ->expectsOutputToContain('Operational health check')
            ->assertExitCode(0);
    }

    public function test_data_quality_command_returns_warning_for_stale_pending_notifications(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'nombre' => 'Rol QA Operacional',
            'slug' => 'rol-qa-operacional',
            'descripcion' => 'Rol para pruebas operacionales',
            'nivel_acceso' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notificaciones_sistema')->insert([
            'titulo' => 'Notificacion vieja',
            'mensaje' => 'Pendiente de atencion',
            'tipo' => 'Alerta',
            'modulo' => 'Sistema',
            'prioridad' => 'Alta',
            'role_id' => $roleId,
            'estado' => 'Pendiente',
            'requiere_accion' => false,
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        $this->artisan('data:quality:check')
            ->expectsOutputToContain('Status: WARN')
            ->assertExitCode(0);

        $this->artisan('data:quality:check --strict')
            ->expectsOutputToContain('Status: WARN')
            ->assertExitCode(1);
    }
}
