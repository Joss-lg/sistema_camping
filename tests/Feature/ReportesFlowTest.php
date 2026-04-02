<?php

namespace Tests\Feature;

use App\Models\CategoriaInsumo;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Permission;
use App\Models\Proveedor;
use App\Models\ReporteGenerado;
use App\Models\Role;
use App\Models\UnidadMedida;
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

    public function test_reporte_de_entregas_filtra_por_fecha_entrega_real_de_la_orden(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-REP-001',
            'razon_social' => 'Proveedor Reportes SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Metro',
            'abreviatura' => 'm',
            'tipo' => 'Longitud',
            'activo' => true,
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Textiles',
            'slug' => 'textiles-reportes',
            'activo' => true,
        ]);

        $insumo = Insumo::query()->create([
            'codigo_insumo' => 'INS-REP-001',
            'nombre' => 'Tela filtrada por recepción',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'proveedor_id' => $proveedor->id,
            'stock_minimo' => 1,
            'stock_actual' => 10,
            'precio_unitario' => 50,
            'estado' => 'Activo',
            'activo' => true,
        ]);

        $ordenFueraDeRango = OrdenCompra::query()->create([
            'numero_orden' => 'OC-REP-001',
            'proveedor_id' => $proveedor->id,
            'user_id' => $user->id,
            'fecha_orden' => now()->subDays(10),
            'fecha_entrega_prevista' => now()->subDays(9)->toDateString(),
            'fecha_entrega_real' => now()->subDays(9)->toDateString(),
            'estado' => 'Recibida',
            'monto_total' => 100,
        ]);

        $ordenEnRango = OrdenCompra::query()->create([
            'numero_orden' => 'OC-REP-002',
            'proveedor_id' => $proveedor->id,
            'user_id' => $user->id,
            'fecha_orden' => now()->subDays(5),
            'fecha_entrega_prevista' => now()->subDays(4)->toDateString(),
            'fecha_entrega_real' => now()->subDay()->toDateString(),
            'estado' => 'Recibida',
            'monto_total' => 120,
        ]);

        OrdenCompraDetalle::query()->create([
            'orden_compra_id' => $ordenFueraDeRango->id,
            'numero_linea' => 1,
            'insumo_id' => $insumo->id,
            'unidad_medida_id' => $unidad->id,
            'cantidad_solicitada' => 15,
            'cantidad_recibida' => 15,
            'cantidad_aceptada' => 15,
            'precio_unitario' => 10,
            'subtotal' => 150,
            'estado_linea' => 'Recibida',
        ]);

        OrdenCompraDetalle::query()->create([
            'orden_compra_id' => $ordenEnRango->id,
            'numero_linea' => 1,
            'insumo_id' => $insumo->id,
            'unidad_medida_id' => $unidad->id,
            'cantidad_solicitada' => 20,
            'cantidad_recibida' => 20,
            'cantidad_aceptada' => 20,
            'precio_unitario' => 10,
            'subtotal' => 200,
            'estado_linea' => 'Recibida',
        ]);

        MovimientoInventario::query()->create([
            'tipo_movimiento' => MovimientoInventario::TIPO_ENTRADA,
            'insumo_id' => $insumo->id,
            'orden_compra_id' => $ordenFueraDeRango->id,
            'cantidad' => 15,
            'unidad_medida_id' => $unidad->id,
            'motivo' => 'ACEPTADO',
            'user_id' => $user->id,
            'fecha_movimiento' => now()->subDay(),
            'saldo_anterior' => 0,
            'saldo_posterior' => 15,
        ]);

        MovimientoInventario::query()->create([
            'tipo_movimiento' => MovimientoInventario::TIPO_ENTRADA,
            'insumo_id' => $insumo->id,
            'orden_compra_id' => $ordenEnRango->id,
            'cantidad' => 20,
            'unidad_medida_id' => $unidad->id,
            'motivo' => 'ACEPTADO',
            'user_id' => $user->id,
            'fecha_movimiento' => now()->subDays(15),
            'saldo_anterior' => 15,
            'saldo_posterior' => 35,
        ]);

        $response = $this->actingAs($user)->get(route('reportes.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('20.00');
        $response->assertDontSeeText('15.00');
    }

    public function test_reporte_de_entregas_muestra_detalles_recibidos_aun_sin_movimientos(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-REP-002',
            'razon_social' => 'Proveedor Sin Movimiento SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad',
            'abreviatura' => 'un',
            'tipo' => 'Conteo',
            'activo' => true,
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Herrajes',
            'slug' => 'herrajes-reportes',
            'activo' => true,
        ]);

        $insumo = Insumo::query()->create([
            'codigo_insumo' => 'INS-REP-002',
            'nombre' => 'Hebilla sin movimiento',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'proveedor_id' => $proveedor->id,
            'stock_minimo' => 1,
            'stock_actual' => 5,
            'precio_unitario' => 12,
            'estado' => 'Activo',
            'activo' => true,
        ]);

        $orden = OrdenCompra::query()->create([
            'numero_orden' => 'OC-REP-003',
            'proveedor_id' => $proveedor->id,
            'user_id' => $user->id,
            'fecha_orden' => now()->subDays(3),
            'fecha_entrega_prevista' => now()->subDays(2)->toDateString(),
            'fecha_entrega_real' => now()->subDay()->toDateString(),
            'estado' => 'Recibida',
            'monto_total' => 240,
        ]);

        OrdenCompraDetalle::query()->create([
            'orden_compra_id' => $orden->id,
            'numero_linea' => 1,
            'insumo_id' => $insumo->id,
            'unidad_medida_id' => $unidad->id,
            'cantidad_solicitada' => 8,
            'cantidad_recibida' => 8,
            'cantidad_aceptada' => 8,
            'precio_unitario' => 30,
            'subtotal' => 240,
            'estado_linea' => 'Recibida',
        ]);

        $response = $this->actingAs($user)->get(route('reportes.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('Hebilla sin movimiento');
        $response->assertSeeText('8.00');
    }

    public function test_reporte_de_entregas_usa_cantidad_solicitada_si_la_orden_fue_marcada_como_recibida_desde_edicion(): void
    {
        $user = $this->crearUsuarioConPermisoReportes();

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-REP-003',
            'razon_social' => 'Proveedor Edicion Manual SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Paquete',
            'abreviatura' => 'paq',
            'tipo' => 'Conteo',
            'activo' => true,
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Empaques',
            'slug' => 'empaques-reportes',
            'activo' => true,
        ]);

        $insumo = Insumo::query()->create([
            'codigo_insumo' => 'INS-REP-003',
            'nombre' => 'Caja plegable',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'proveedor_id' => $proveedor->id,
            'stock_minimo' => 1,
            'stock_actual' => 2,
            'precio_unitario' => 18,
            'estado' => 'Activo',
            'activo' => true,
        ]);

        $orden = OrdenCompra::query()->create([
            'numero_orden' => 'OC-REP-004',
            'proveedor_id' => $proveedor->id,
            'user_id' => $user->id,
            'fecha_orden' => now()->subDays(2),
            'fecha_entrega_prevista' => now()->subDay()->toDateString(),
            'fecha_entrega_real' => now()->toDateString(),
            'estado' => 'Recibida',
            'monto_total' => 180,
        ]);

        OrdenCompraDetalle::query()->create([
            'orden_compra_id' => $orden->id,
            'numero_linea' => 1,
            'insumo_id' => $insumo->id,
            'unidad_medida_id' => $unidad->id,
            'cantidad_solicitada' => 10,
            'cantidad_recibida' => 0,
            'cantidad_aceptada' => 0,
            'precio_unitario' => 18,
            'subtotal' => 180,
            'estado_linea' => 'Pendiente',
        ]);

        $response = $this->actingAs($user)->get(route('reportes.index', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('Caja plegable');
        $response->assertSeeText('10.00');
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
