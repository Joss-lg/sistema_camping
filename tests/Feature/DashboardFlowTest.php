<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard_operativo(): void
    {
        $role = Role::query()->create([
            'nombre' => 'Supervisor Dashboard',
            'slug' => 'supervisor-dashboard',
            'nivel_acceso' => 50,
        ]);

        Permission::query()->create([
            'role_id' => $role->id,
            'modulo' => 'Dashboard',
            'puede_ver' => true,
            'puede_crear' => false,
            'puede_editar' => false,
            'puede_eliminar' => false,
            'puede_aprobar' => false,
        ]);

        $user = User::query()->create([
            'name' => 'Usuario Dashboard',
            'email' => 'dashboard-user@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'activo' => true,
            'departamento' => 'operaciones',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Dashboard');
        $response->assertSeeText('Panel SSR operativo');
        $response->assertSeeText('Órdenes de Producción');
    }
}
