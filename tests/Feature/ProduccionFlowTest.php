<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProduccionFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_order_and_register_material_consumption(): void
    {
        if (! Schema::hasTable('estado') || ! Schema::hasTable('orden_produccion') || ! Schema::hasTable('material')) {
            $this->markTestSkipped('El entorno de pruebas no tiene el esquema de dominio de produccion cargado.');
        }

        $estadoGeneralId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioId = DB::table('usuario')->insertGetId([
            'nombre' => 'Admin Prueba',
            'email' => 'admin-produccion@example.test',
            'password' => bcrypt('secret123'),
            'rol' => 'ADMIN',
            'estado_id' => $estadoGeneralId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoriaProductoId = DB::table('categoria_producto')->insertGetId([
            'nombre' => 'Categoria Test Produccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadId = DB::table('unidad_medida')->insertGetId([
            'nombre' => 'Unidad Test Produccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estadoProductoId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'producto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productoId = DB::table('producto_terminado')->insertGetId([
            'nombre' => 'Producto Test Produccion',
            'sku' => 'SKU-PROD-TEST-001',
            'categoria_id' => $categoriaProductoId,
            'unidad_id' => $unidadId,
            'stock' => 0,
            'stock_minimo' => 0,
            'stock_maximo' => 100,
            'precio_venta' => 10,
            'estado_id' => $estadoProductoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoriaMaterialId = DB::table('categoria_material')->insertGetId([
            'nombre' => 'Categoria Material Test Produccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $materialId = DB::table('material')->insertGetId([
            'nombre' => 'Material Test Produccion',
            'categoria_id' => $categoriaMaterialId,
            'unidad_id' => $unidadId,
            'stock' => 50,
            'stock_minimo' => 5,
            'stock_maximo' => 120,
            'proveedor_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Admin Prueba',
        ])->post(route('produccion.store'), [
            'producto_id' => $productoId,
            'cantidad' => 20,
        ])->assertRedirect(route('produccion.index'));

        $ordenId = (int) DB::table('orden_produccion')->where('producto_id', $productoId)->max('id');

        $this->assertTrue($ordenId > 0);

        $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Admin Prueba',
        ])->post(route('produccion.registrar-consumo'), [
            'orden_produccion_id' => $ordenId,
            'material_id' => $materialId,
            'cantidad_necesaria' => 8,
            'cantidad_usada' => 8,
        ])->assertRedirect(route('produccion.index'));

        $this->assertDatabaseHas('uso_material', [
            'orden_produccion_id' => $ordenId,
            'material_id' => $materialId,
        ]);

        $stockMaterial = (float) DB::table('material')->where('id', $materialId)->value('stock');
        $this->assertEquals(42.0, $stockMaterial);
    }
}
