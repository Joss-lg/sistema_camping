<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TerminadosFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_register_ingreso_and_ajuste_de_stock(): void
    {
        if (! Schema::hasTable('producto_terminado')
            || ! Schema::hasTable('orden_produccion')
            || ! Schema::hasTable('estado')
            || ! Schema::hasColumn('orden_produccion', 'cantidad_ingresada')) {
            $this->markTestSkipped('El entorno de pruebas no tiene el esquema de terminados cargado.');
        }

        $estadoGeneralId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioId = DB::table('usuario')->insertGetId([
            'nombre' => 'Admin Terminados',
            'email' => 'admin-terminados@example.test',
            'password' => bcrypt('secret123'),
            'rol' => 'ADMIN',
            'estado_id' => $estadoGeneralId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoriaProductoId = DB::table('categoria_producto')->insertGetId([
            'nombre' => 'Cat Terminados Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadId = DB::table('unidad_medida')->insertGetId([
            'nombre' => 'Unidad Terminados Test',
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
            'nombre' => 'Producto Terminado Test',
            'sku' => 'SKU-TERM-TEST-001',
            'categoria_id' => $categoriaProductoId,
            'unidad_id' => $unidadId,
            'stock' => 5,
            'stock_minimo' => 1,
            'stock_maximo' => 100,
            'precio_venta' => 15,
            'estado_id' => $estadoProductoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estadoFinalizadaId = DB::table('estado')->insertGetId([
            'nombre' => 'FINALIZADA',
            'tipo' => 'produccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ordenId = DB::table('orden_produccion')->insertGetId([
            'producto_id' => $productoId,
            'cantidad' => 20,
            'cantidad_completada' => 12,
            'cantidad_ingresada' => 0,
            'fecha_inicio' => now(),
            'fecha_esperada' => now(),
            'estado_id' => $estadoFinalizadaId,
            'usuario_id' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Admin Terminados',
        ])->post(route('terminados.ingresos.store'), [
            'orden_produccion_id' => $ordenId,
            'cantidad_ingreso' => 7,
        ])->assertRedirect(route('terminados.index'));

        $stockDespuesIngreso = (float) DB::table('producto_terminado')->where('id', $productoId)->value('stock');
        $this->assertEquals(12.0, $stockDespuesIngreso);

        $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Admin Terminados',
        ])->post(route('terminados.ajustes.store'), [
            'producto_id' => $productoId,
            'tipo_ajuste' => 'RESTAR',
            'cantidad' => 2,
            'motivo' => 'Control de calidad',
        ])->assertRedirect(route('terminados.index'));

        $stockFinal = (float) DB::table('producto_terminado')->where('id', $productoId)->value('stock');
        $this->assertEquals(10.0, $stockFinal);
    }
}
