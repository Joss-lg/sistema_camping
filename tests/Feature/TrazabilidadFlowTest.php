<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrazabilidadFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_search_lote_and_see_timeline(): void
    {
        if (! Schema::hasTable('producto_lote')
            || ! Schema::hasTable('paso_trazabilidad')
            || ! Schema::hasTable('producto_terminado')
            || ! Schema::hasTable('usuario')) {
            $this->markTestSkipped('El entorno de pruebas no tiene el esquema de trazabilidad cargado.');
        }

        $estadoGeneralId = DB::table('estado')->insertGetId([
            'nombre' => 'Activo',
            'tipo' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioId = DB::table('usuario')->insertGetId([
            'nombre' => 'Usuario Trazabilidad',
            'email' => 'traza-user@example.test',
            'password' => bcrypt('secret123'),
            'rol' => 'ADMIN',
            'estado_id' => $estadoGeneralId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoriaProductoId = DB::table('categoria_producto')->insertGetId([
            'nombre' => 'Cat Traza Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadId = DB::table('unidad_medida')->insertGetId([
            'nombre' => 'Unidad Traza Test',
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
            'nombre' => 'Producto Traza Test',
            'sku' => 'SKU-TRAZA-001',
            'categoria_id' => $categoriaProductoId,
            'unidad_id' => $unidadId,
            'stock' => 12,
            'stock_minimo' => 1,
            'stock_maximo' => 50,
            'precio_venta' => 20,
            'estado_id' => $estadoProductoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estadoLoteId = DB::table('estado')->insertGetId([
            'nombre' => 'DISPONIBLE',
            'tipo' => 'lote',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $loteId = DB::table('producto_lote')->insertGetId([
            'producto_id' => $productoId,
            'numero_lote' => 'LOTE-TRAZA-001',
            'fecha_produccion' => now(),
            'estado_id' => $estadoLoteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paso_trazabilidad')->insert([
            'lote_id' => $loteId,
            'etapa' => 'INGRESO_TERMINADO',
            'descripcion' => 'Ingreso inicial de prueba',
            'fecha' => now(),
            'usuario_id' => $usuarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'auth_user_id' => $usuarioId,
            'auth_user_rol' => 'ADMIN',
            'auth_user_nombre' => 'Usuario Trazabilidad',
        ])->get(route('trazabilidad.index', ['q' => 'LOTE-TRAZA-001', 'lote_id' => $loteId]));

        $response->assertOk();
        $response->assertSeeText('LOTE-TRAZA-001');
        $response->assertSeeText('INGRESO_TERMINADO');
        $response->assertSeeText('Producto Traza Test');
    }
}
