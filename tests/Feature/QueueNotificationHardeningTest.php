<?php

namespace Tests\Feature;

use App\Jobs\CalcularCostosPromedioJob;
use App\Jobs\VerificarStockBajoTerminadosJob;
use App\Models\CategoriaInsumo;
use App\Models\InventarioProductoTerminado;
use App\Models\NotificacionSistema;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\TipoProducto;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueNotificationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcular_costos_job_actualiza_costos_y_notifica_solo_roles_objetivo(): void
    {
        $rolAdmin = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $rolGerente = Role::query()->create([
            'nombre' => 'Gerente de Produccion',
            'slug' => 'gerente_produccion',
            'nivel_acceso' => 80,
        ]);

        $rolOperario = Role::query()->create([
            'nombre' => 'Operario',
            'slug' => 'operario',
            'nivel_acceso' => 10,
        ]);

        $admin = User::query()->create([
            'name' => 'Admin Costos',
            'email' => 'admin-costos@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        $gerente = User::query()->create([
            'name' => 'Gerente Costos',
            'email' => 'gerente-costos@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolGerente->id,
            'activo' => true,
        ]);

        $operario = User::query()->create([
            'name' => 'Operario Costos',
            'email' => 'operario-costos@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolOperario->id,
            'activo' => true,
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad Costos',
            'abreviatura' => 'UC',
            'tipo' => 'Cantidad',
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Categoria Costos',
            'slug' => 'categoria-costos',
            'activo' => true,
        ]);

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-COSTOS',
            'razon_social' => 'Proveedor Costos SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        $insumo = \App\Models\Insumo::query()->create([
            'codigo_insumo' => 'INS-COSTOS-01',
            'nombre' => 'Insumo Costos',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'proveedor_id' => $proveedor->id,
            'stock_minimo' => 1,
            'stock_actual' => 10,
            'precio_unitario' => 120,
            'estado' => 'Activo',
            'activo' => true,
        ]);

        $ordenCompra = OrdenCompra::query()->create([
            'numero_orden' => 'OC-COSTOS-001',
            'proveedor_id' => $proveedor->id,
            'user_id' => $admin->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'fecha_entrega_real' => now()->toDateString(),
            'estado' => 'Recibida',
            'total_items' => 1,
            'total_cantidad' => 10,
            'subtotal' => 900,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 900,
        ]);

        OrdenCompraDetalle::query()->create([
            'orden_compra_id' => $ordenCompra->id,
            'numero_linea' => 1,
            'insumo_id' => $insumo->id,
            'unidad_medida_id' => $unidad->id,
            'cantidad_solicitada' => 10,
            'cantidad_recibida' => 10,
            'cantidad_aceptada' => 10,
            'precio_unitario' => 100,
            'descuento_porcentaje' => 0,
            'subtotal' => 1000,
            'estado_linea' => 'Recibida',
        ]);

        (new CalcularCostosPromedioJob())->handle();

        $insumo->refresh();

        $this->assertEquals(100.0, (float) $insumo->precio_costo);

        $notificacionesAdmin = NotificacionSistema::query()
            ->where('modulo', 'Compras')
            ->where('user_id', $admin->id)
            ->where('metadata->origen', 'job.calcular_costos_promedio')
            ->get();

        $notificacionesGerente = NotificacionSistema::query()
            ->where('modulo', 'Compras')
            ->where('user_id', $gerente->id)
            ->where('metadata->origen', 'job.calcular_costos_promedio')
            ->get();

        $notificacionesOperario = NotificacionSistema::query()
            ->where('modulo', 'Compras')
            ->where('user_id', $operario->id)
            ->where('metadata->origen', 'job.calcular_costos_promedio')
            ->get();

        $this->assertCount(1, $notificacionesAdmin);
        $this->assertCount(1, $notificacionesGerente);
        $this->assertCount(0, $notificacionesOperario);
    }

    public function test_verificar_stock_bajo_terminados_no_duplica_notificaciones_el_mismo_dia(): void
    {
        $rolAdmin = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $rolNoObjetivo = Role::query()->create([
            'nombre' => 'Invitado',
            'slug' => 'invitado',
            'nivel_acceso' => 1,
        ]);

        $admin = User::query()->create([
            'name' => 'Admin Terminados',
            'email' => 'admin-terminados@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        User::query()->create([
            'name' => 'Invitado Terminados',
            'email' => 'invitado-terminados@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolNoObjetivo->id,
            'activo' => true,
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad Terminados',
            'abreviatura' => 'UT',
            'tipo' => 'Cantidad',
        ]);

        $tipoProducto = TipoProducto::query()->create([
            'nombre' => 'Carpa Test',
            'slug' => 'carpa-test',
            'activo' => true,
            'stock_minimo_terminado' => 10,
        ]);

        $ubicacion = UbicacionAlmacen::query()->create([
            'codigo_ubicacion' => 'ALM-TERM-01',
            'nombre' => 'Almacen Terminados',
            'tipo' => 'Almacen General',
            'capacidad_actual' => 0,
            'activo' => true,
        ]);

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => $tipoProducto->id,
            'user_id' => $admin->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 5,
            'unidad_medida_id' => $unidad->id,
            'estado' => OrdenProduccion::ESTADO_EN_PROCESO,
        ]);

        $producto = ProductoTerminado::query()->create([
            'numero_lote_produccion' => 'LOTE-QN-001',
            'orden_produccion_id' => $orden->id,
            'tipo_producto_id' => $tipoProducto->id,
            'user_responsable_id' => $admin->id,
            'cantidad_producida' => 5,
            'unidad_medida_id' => $unidad->id,
            'estado' => 'Aprobado',
            'estado_calidad' => 'Aprobado',
        ]);

        $inventario = InventarioProductoTerminado::query()->create([
            'producto_terminado_id' => $producto->id,
            'tipo_producto_id' => $tipoProducto->id,
            'ubicacion_almacen_id' => $ubicacion->id,
            'cantidad_en_almacen' => 5,
            'unidad_medida_id' => $unidad->id,
            'cantidad_reservada' => 0,
            'fecha_ingreso_almacen' => now()->toDateString(),
            'estado' => 'En Almacén',
            'precio_unitario' => 100,
            'valor_total_inventario' => 500,
        ]);

        $job = new VerificarStockBajoTerminadosJob();
        $job->handle();
        $job->handle();

        $notificaciones = NotificacionSistema::query()
            ->where('modulo', 'Terminados')
            ->where('user_id', $admin->id)
            ->where('metadata->inventario_id', $inventario->id)
            ->get();

        $this->assertCount(1, $notificaciones);
        $this->assertSame('Alta', $notificaciones->first()->prioridad);
    }
}
