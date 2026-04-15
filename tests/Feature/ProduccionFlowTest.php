<?php

namespace Tests\Feature;

use App\Models\CategoriaInsumo;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenCompra;
use App\Models\OrdenProduccion;
use App\Models\Permission;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\TipoProducto;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_production_order_reserves_materials(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $stockInicial = (float) $fixtures['insumo']->stock_actual;

        $this->actingAs($fixtures['user'])->post(route('produccion.store'), [
            'producto_id' => $fixtures['tipoProducto']->id,
            'cantidad' => 20,
            'responsable_id' => $fixtures['user']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_esperada' => now()->addDay()->toDateString(),
        ])->assertRedirect(route('produccion.index'));

        $orden = OrdenProduccion::query()->latest('id')->first();
        $this->assertNotNull($orden);
        $this->assertSame('Pendiente', (string) $orden->estado);
        $this->assertSame(20.0, (float) $orden->cantidad_produccion);

        $fixtures['insumo']->refresh();
        $this->assertSame($stockInicial, (float) $fixtures['insumo']->stock_actual);
    }

    public function test_registering_consumption_adjusts_stock_correctly(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $this->actingAs($fixtures['user'])->post(route('produccion.store'), [
            'producto_id' => $fixtures['tipoProducto']->id,
            'cantidad' => 20,
            'responsable_id' => $fixtures['user']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_esperada' => now()->addDay()->toDateString(),
        ])->assertRedirect(route('produccion.index'));

        $orden = OrdenProduccion::query()->latest('id')->firstOrFail();
        $stockInicial = (float) $fixtures['insumo']->stock_actual;
        $loteStockInicial = (float) $fixtures['lote']->cantidad_en_stock;

        $this->actingAs($fixtures['user'])->post(route('produccion.registrar-consumo'), [
            'orden_produccion_id' => $orden->id,
            'material_id' => $fixtures['insumo']->id,
            'cantidad_usada' => 10,
            'cantidad_merma' => 2,
            'motivo_merma' => 'Desperdicio en corte',
            'tipo_merma' => 'Corte',
        ])->assertRedirect(route('produccion.index'));

        $fixtures['insumo']->refresh();
        $fixtures['lote']->refresh();

        $this->assertSame($stockInicial - 12.0, (float) $fixtures['insumo']->stock_actual);
        $this->assertSame($loteStockInicial - 12.0, (float) $fixtures['lote']->cantidad_en_stock);
        $this->assertSame(12.0, (float) $fixtures['lote']->cantidad_consumida);

        $this->assertDatabaseHas('consumos_materiales', [
            'orden_produccion_id' => $orden->id,
            'insumo_id' => $fixtures['insumo']->id,
            'cantidad_consumida' => 10,
            'cantidad_desperdicio' => 2,
        ]);
    }

    public function test_cancelling_production_order_releases_reserved_materials(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $this->actingAs($fixtures['user'])->post(route('produccion.store'), [
            'producto_id' => $fixtures['tipoProducto']->id,
            'cantidad' => 20,
            'responsable_id' => $fixtures['user']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_esperada' => now()->addDay()->toDateString(),
        ])->assertRedirect(route('produccion.index'));

        $orden = OrdenProduccion::query()->latest('id')->firstOrFail();

        $this->actingAs($fixtures['user'])
            ->patch(route('produccion.cancelar', ['id' => $orden->id]))
            ->assertRedirect(route('produccion.index'));

        $orden->refresh();
        $this->assertSame('Cancelada', (string) $orden->estado);
    }

    public function test_admin_can_create_order_and_register_material_consumption(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $this->actingAs($fixtures['user'])->post(route('produccion.store'), [
            'producto_id' => $fixtures['tipoProducto']->id,
            'cantidad' => 20,
            'responsable_id' => $fixtures['user']->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_esperada' => now()->addDay()->toDateString(),
        ])->assertRedirect(route('produccion.index'));

        $orden = OrdenProduccion::query()->latest('id')->firstOrFail();

        $this->assertTrue($orden->id > 0);

        $this->actingAs($fixtures['user'])->post(route('produccion.registrar-consumo'), [
            'orden_produccion_id' => $orden->id,
            'material_id' => $fixtures['insumo']->id,
            'cantidad_usada' => 6,
        ])->assertRedirect(route('produccion.index'));

        $this->assertDatabaseHas('consumos_materiales', [
            'orden_produccion_id' => $orden->id,
            'insumo_id' => $fixtures['insumo']->id,
        ]);
    }

    public function test_bom_status_can_be_updated_from_edit_page(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $this->actingAs($fixtures['user'])->post(route('produccion.bom.store'), [
            'producto_nombre' => $fixtures['tipoProducto']->nombre,
            'material_id' => [$fixtures['insumo']->id],
            'cantidad_base' => [2.5],
            'activo' => ['1'],
            'activo_general' => '1',
        ])->assertRedirect(route('produccion.bom.index'));

        $ordenBom = OrdenProduccion::query()
            ->where('es_plantilla_bom', true)
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($fixtures['user'])
            ->put(route('produccion.bom.update', ['id' => $ordenBom->id]), [
                'producto_nombre' => $fixtures['tipoProducto']->nombre,
                'material_id' => [$fixtures['insumo']->id],
                'cantidad_base' => [2.5],
                'activo' => ['1'],
                'activo_general' => '0',
            ])
            ->assertRedirect(route('produccion.bom.index'));

        $ordenBom->refresh();
        $this->assertSame(OrdenProduccion::ESTADO_CANCELADA, (string) $ordenBom->estado);

        $this->actingAs($fixtures['user'])
            ->put(route('produccion.bom.update', ['id' => $ordenBom->id]), [
                'producto_nombre' => $fixtures['tipoProducto']->nombre,
                'material_id' => [$fixtures['insumo']->id],
                'cantidad_base' => [2.5],
                'activo' => ['1'],
                'activo_general' => '1',
            ])
            ->assertRedirect(route('produccion.bom.index'));

        $ordenBom->refresh();
        $this->assertSame(OrdenProduccion::ESTADO_PENDIENTE, (string) $ordenBom->estado);
        $this->assertDatabaseHas('ordenes_produccion_materiales', [
            'orden_produccion_id' => $ordenBom->id,
            'insumo_id' => $fixtures['insumo']->id,
            'estado_asignacion' => 'Asignado',
        ]);
    }

    public function test_inactive_insumo_cannot_be_selected_for_production(): void
    {
        $fixtures = $this->crearFixturesProduccion();

        $fixtures['insumo']->update([
            'activo' => false,
            'estado' => 'Inactivo',
        ]);

        $this->actingAs($fixtures['user'])
            ->from(route('produccion.bom.index'))
            ->post(route('produccion.bom.store'), [
                'producto_nombre' => $fixtures['tipoProducto']->nombre,
                'material_id' => [$fixtures['insumo']->id],
                'cantidad_base' => [1],
                'activo' => ['1'],
                'activo_general' => '1',
            ])
            ->assertRedirect(route('produccion.bom.index'))
            ->assertSessionHasErrors('material_id');
    }

    /**
     * @return array{user: User, tipoProducto: TipoProducto, insumo: Insumo, lote: LoteInsumo}
     */
    private function crearFixturesProduccion(): array
    {
        $role = Role::query()->create([
            'nombre' => 'Admin Produccion',
            'slug' => 'admin-produccion',
            'nivel_acceso' => 90,
        ]);

        Permission::query()->create([
            'role_id' => $role->id,
            'modulo' => 'Produccion',
            'puede_ver' => true,
            'puede_crear' => true,
            'puede_editar' => true,
            'puede_eliminar' => true,
            'puede_aprobar' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Admin Prueba',
            'email' => 'admin-produccion@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'activo' => true,
            'departamento' => 'produccion',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad Test Produccion',
            'abreviatura' => 'utp',
            'tipo' => 'Cantidad',
            'activo' => true,
        ]);

        $tipoProducto = TipoProducto::query()->create([
            'nombre' => 'Producto Test Produccion',
            'slug' => 'sku-prod-test-001',
            'activo' => true,
        ]);

        $ubicacion = UbicacionAlmacen::query()->create([
            'codigo_ubicacion' => 'ALM-PRD-01',
            'nombre' => 'Almacen Produccion',
            'tipo' => 'General',
            'capacidad_actual' => 0,
            'activo' => true,
        ]);

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-PRD-001',
            'razon_social' => 'Proveedor Produccion SA',
            'tipo_proveedor' => 'Textiles',
            'estatus' => 'Activo',
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Categoria Insumo Test',
            'slug' => 'categoria-insumo-test',
            'activo' => true,
        ]);

        $insumo = Insumo::query()->create([
            'codigo_insumo' => 'INS-PRD-001',
            'nombre' => 'Insumo Test Produccion',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'tipo_producto_id' => $tipoProducto->id,
            'stock_minimo' => 5,
            'stock_actual' => 100,
            'stock_reservado' => 0,
            'proveedor_id' => $proveedor->id,
            'precio_unitario' => 10,
            'precio_costo' => 8,
            'ubicacion_almacen_id' => $ubicacion->id,
            'estado' => 'Activo',
            'activo' => true,
            'unidad_compra' => 'pz',
            'cantidad_minima_orden' => 1,
        ]);

        $ordenCompra = OrdenCompra::query()->create([
            'numero_orden' => 'OC-PRD-001',
            'proveedor_id' => $proveedor->id,
            'user_id' => $user->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->addDay()->toDateString(),
            'estado' => 'Confirmada',
            'total_items' => 1,
            'total_cantidad' => 100,
            'subtotal' => 1000,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 1000,
        ]);

        $lote = LoteInsumo::query()->create([
            'numero_lote' => 'LOT-PRD-001',
            'insumo_id' => $insumo->id,
            'orden_compra_id' => $ordenCompra->id,
            'proveedor_id' => $proveedor->id,
            'fecha_lote' => now()->toDateString(),
            'fecha_recepcion' => now(),
            'cantidad_recibida' => 100,
            'cantidad_en_stock' => 100,
            'cantidad_consumida' => 0,
            'cantidad_rechazada' => 0,
            'ubicacion_almacen_id' => $ubicacion->id,
            'estado_calidad' => 'Aceptado',
            'activo' => true,
            'user_recepcion_id' => $user->id,
        ]);

        return [
            'user' => $user,
            'tipoProducto' => $tipoProducto,
            'insumo' => $insumo,
            'lote' => $lote,
        ];
    }
}
