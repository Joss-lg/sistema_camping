<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportesFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_view_reportes_and_export_csv(): void
    {
        if (! Schema::hasTable('estado') || ! Schema::hasTable('usuario')) {
            $this->markTestSkipped('El entorno de pruebas no tiene el esquema base cargado.');
        }

        $estadoId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioId = DB::table('usuario')->insertGetId([
            'nombre' => 'Usuario Reportes',
            'email' => 'reportes-user@example.test',
            'password' => bcrypt('secret123'),
            'rol' => 'ADMIN',
            'estado_id' => $estadoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = [
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Usuario Reportes',
        ];

        $responseView = $this->withSession($session)->get(route('reportes.index'));
        $responseView->assertOk();
        $responseView->assertSeeText('Reportes');

        $responseCsv = $this->withSession($session)->get(route('reportes.export.csv', [
            'type' => 'entregas',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $responseCsv->assertOk();
        $responseCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
