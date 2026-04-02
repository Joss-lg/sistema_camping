<?php

namespace Tests\Feature;

use App\Models\EtapaProduccionPlantilla;
use App\Models\InventarioProductoTerminado;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\Role;
use App\Models\TipoProducto;
use App\Models\UbicacionAlmacen;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionEventosAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_last_stage_completes_order_and_creates_finished_inventory(): void
    {
        $role = Role::query()->create([
            'nombre' => 'Administrador Test',
            'slug' => 'admin-test-eventos',
            'nivel_acceso' => 100,
        ]);

        $user = User::query()->create([
            'name' => 'Usuario Eventos',
            'email' => 'eventos-produccion@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Unidad Eventos',
            'abreviatura' => 'UEV',
            'tipo' => 'Cantidad',
        ]);

        $tipoProducto = TipoProducto::query()->create([
            'nombre' => 'Tipo Producto Eventos',
            'slug' => 'tipo-producto-eventos',
            'activo' => true,
        ]);

        $ubicacion = UbicacionAlmacen::query()->create([
            'codigo_ubicacion' => 'ALM-EV-01',
            'nombre' => 'Almacen Eventos',
            'tipo' => 'Almacen General',
            'capacidad_actual' => 0,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::query()->create([
            'nombre' => 'Etapa 1',
            'codigo' => 'EVT-ETP-1',
            'tipo_producto_id' => $tipoProducto->id,
            'numero_secuencia' => 1,
            'duracion_estimada_minutos' => 30,
            'cantidad_operarios' => 1,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::query()->create([
            'nombre' => 'Etapa 2',
            'codigo' => 'EVT-ETP-2',
            'tipo_producto_id' => $tipoProducto->id,
            'numero_secuencia' => 2,
            'duracion_estimada_minutos' => 45,
            'cantidad_operarios' => 1,
            'activo' => true,
        ]);

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => $tipoProducto->id,
            'user_id' => $user->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 12,
            'unidad_medida_id' => $unidad->id,
            'estado' => 'Pendiente',
            'costo_real' => 120,
        ]);

        $orden->generarEtapasTrazabilidad();

        $etapaUno = $orden->trazabilidadEtapas()->where('numero_secuencia', 1)->firstOrFail();
        $etapaDos = $orden->trazabilidadEtapas()->where('numero_secuencia', 2)->firstOrFail();

        $etapaUno->iniciar();
        $etapaUno->completar();

        $etapaDos->refresh();
        $orden->refresh();

        $this->assertEquals('En Proceso', $orden->estado);
        $this->assertEquals('Esperando Aprobación', $etapaDos->estado);
        $this->assertEquals(1, (int) $orden->etapas_completadas);

        $etapaDos->completar();

        $orden->refresh();

        $this->assertEquals('Finalizada', $orden->estado);
        $this->assertEquals(2, (int) $orden->etapas_completadas);
        $this->assertEquals(100.0, (float) $orden->porcentaje_completado);

        $productoTerminado = ProductoTerminado::query()
            ->where('orden_produccion_id', $orden->id)
            ->where('tipo_producto_id', $tipoProducto->id)
            ->first();

        $this->assertNotNull($productoTerminado);
        $this->assertEquals(12.0, (float) $productoTerminado->cantidad_producida);

        $inventario = InventarioProductoTerminado::query()
            ->where('producto_terminado_id', $productoTerminado->id)
            ->where('ubicacion_almacen_id', $ubicacion->id)
            ->first();

        $this->assertNotNull($inventario);
        $this->assertEquals(12.0, (float) $inventario->cantidad_en_almacen);
        $this->assertEquals(InventarioProductoTerminado::ESTADO_PENDIENTE_INSPECCION, $inventario->estado);

        // Idempotencia: completar una orden ya completada no debe duplicar registros.
        $orden->marcarCompletada();

        $this->assertEquals(1, ProductoTerminado::query()->where('orden_produccion_id', $orden->id)->count());
        $this->assertEquals(1, InventarioProductoTerminado::query()->where('producto_terminado_id', $productoTerminado->id)->count());
    }
}
