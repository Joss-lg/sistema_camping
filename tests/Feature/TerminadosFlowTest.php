<?php

namespace Tests\Feature;

use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TipoProducto;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminadosFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_ingreso_and_ajuste_de_stock(): void
    {
        [$user, $orden, $ubicacion] = $this->crearEscenarioTerminados();

        $this->actingAs($user)->post(route('terminados.ingresos.store'), [
            'orden_produccion_id' => $orden->id,
            'cantidad_ingreso' => 7,
            'ubicacion_almacen_id' => $ubicacion->id,
        ])->assertRedirect(route('terminados.index'));

        $inventario = InventarioProductoTerminado::query()->latest('id')->first();
        $this->assertNotNull($inventario);
        $this->assertSame(7.0, (float) $inventario->cantidad_en_almacen);
        $this->assertSame('En Almacén', (string) $inventario->estado);

        $this->actingAs($user)->post(route('terminados.ajustes.store'), [
            'producto_id' => $inventario->id,
            'tipo_ajuste' => 'RESTAR',
            'cantidad' => 2,
            'motivo' => 'Control de calidad',
        ])->assertRedirect(route('terminados.index'));

        $inventario->refresh();
        $this->assertSame(5.0, (float) $inventario->cantidad_en_almacen);
    }

    /**
     * @return array{0: User, 1: OrdenProduccion, 2: UbicacionAlmacen}
     */
    private function crearEscenarioTerminados(): array
    {
        $role = Role::query()->create([
            'nombre' => 'Admin Terminados',
            'slug' => 'admin-terminados',
            'nivel_acceso' => 80,
        ]);

        Permission::query()->create([
            'role_id' => $role->id,
            'modulo' => 'Terminados',
            'puede_ver' => true,
            'puede_crear' => true,
            'puede_editar' => true,
            'puede_eliminar' => true,
            'puede_aprobar' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Admin Terminados',
            'email' => 'admin-terminados@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'activo' => true,
            'departamento' => 'almacen',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad Terminados Test',
            'abreviatura' => 'utt',
            'tipo' => 'Cantidad',
            'activo' => true,
        ]);

        $tipoProducto = TipoProducto::query()->create([
            'nombre' => 'Producto Terminado Test',
            'slug' => 'sku-term-test-001',
            'activo' => true,
        ]);

        $ubicacion = UbicacionAlmacen::query()->create([
            'codigo_ubicacion' => 'ALM-TERM-01',
            'nombre' => 'Almacen Terminados',
            'tipo' => 'General',
            'capacidad_actual' => 0,
            'activo' => true,
        ]);

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => $tipoProducto->id,
            'user_id' => $user->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 20,
            'unidad_medida_id' => $unidad->id,
            'estado' => OrdenProduccion::ESTADO_FINALIZADA,
            'etapas_totales' => 1,
            'etapas_completadas' => 1,
            'porcentaje_completado' => 100,
            'costo_real' => 140,
        ]);

        return [$user, $orden, $ubicacion];
    }
}
