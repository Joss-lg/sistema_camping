<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\ReporteGenerado;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_reportes_with_current_schema(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $response = $this->actingAs($user)->get(route('reportes.index'));

        $response->assertOk();
        $response->assertSeeText('Reportes y');
        $response->assertSeeText('Aplicar filtros');
    }

    public function test_csv_export_creates_reporte_generado_and_uses_valid_headers(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $responseCsv = $this->actingAs($user)->get(route('reportes.export.csv', [
            'type' => 'entregas',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $responseCsv->assertOk();
        $responseCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $responseCsv->assertHeader('content-disposition');
        $this->assertStringContainsString('id,fecha_entrega,proveedor,material,cantidad,calidad', $responseCsv->getContent());

        $this->assertDatabaseHas('reportes_generados', [
            'tipo_reporte' => 'entregas',
            'formato' => 'csv',
            'estado' => 'Descargado',
            'generado_por_user_id' => $user->id,
        ]);

        $registro = ReporteGenerado::query()->latest('id')->first();
        $this->assertNotNull($registro);
        $this->assertNotNull($registro->expiracion_at);
        $this->assertIsArray($registro->parametros);
        $this->assertSame('entregas', $registro->parametros['type'] ?? null);
    }

    public function test_csv_export_swaps_invalid_range_order_in_stored_params(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $from = now()->toDateString();
        $to = now()->subDays(3)->toDateString();

        $responseCsv = $this->actingAs($user)->get(route('reportes.export.csv', [
            'type' => 'entregas',
            'from' => $from,
            'to' => $to,
        ]));

        $responseCsv->assertOk();

        $registro = ReporteGenerado::query()->latest('id')->first();
        $this->assertNotNull($registro);
        $this->assertSame($to, $registro->fecha_desde?->toDateString());
        $this->assertSame($from, $registro->fecha_hasta?->toDateString());
    }

    private function crearUsuarioConPermisoReportes(): User
    {
        $role = Role::query()->create([
            'nombre' => 'Supervisor Reportes',
            'slug' => 'supervisor-reportes',
            'nivel_acceso' => 60,
        ]);

        Permission::query()->create([
            'role_id' => $role->id,
            'modulo' => 'Reportes',
            'puede_ver' => true,
            'puede_crear' => true,
            'puede_editar' => true,
            'puede_eliminar' => false,
            'puede_aprobar' => false,
        ]);

        return User::query()->create([
            'name' => 'Usuario Reportes',
            'email' => 'reportes-user@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'activo' => true,
            'departamento' => 'administracion',
        ]);
    }
}
