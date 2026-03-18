<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_view_dashboard_operativo(): void
    {
        if (! Schema::hasTable('estado')
            || ! Schema::hasTable('usuario')
            || ! Schema::hasTable('entrega_proveedor')
            || ! Schema::hasTable('material')
            || ! Schema::hasTable('orden_produccion')
            || ! Schema::hasTable('producto_lote')) {
            $this->markTestSkipped('El entorno de pruebas no tiene el esquema de dashboard cargado.');
        }

        $estadoId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioId = DB::table('usuario')->insertGetId([
            'nombre' => 'Usuario Dashboard',
            'email' => 'dashboard-user@example.test',
            'password' => bcrypt('secret123'),
            'rol' => 'ADMIN',
            'estado_id' => $estadoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Usuario Dashboard',
        ])->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Dashboard Operativo');
        $response->assertSeeText('Ordenes en proceso');
        $response->assertSeeText('Accesos rapidos');
    }
}
