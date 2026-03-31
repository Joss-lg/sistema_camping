<?php

namespace Tests\Feature;

use App\Models\EtapaProduccionPlantilla;
use App\Models\OrdenProduccion;
use App\Models\Permission;
use App\Models\ProductoTerminado;
use App\Models\Role;
use App\Models\TipoProducto;
use App\Models\TrazabilidadEtapa;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrazabilidadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_search_lote_and_access_trazabilidad_views(): void
    {
        [$user, $orden, $productoTerminado] = $this->crearEscenarioBase();

        $response = $this->actingAs($user)
            ->get(route('trazabilidad.index', ['q' => $productoTerminado->numero_lote_produccion]));

        $response->assertOk();
        $response->assertSeeText($productoTerminado->numero_serie);
        $response->assertSeeText($orden->tipoProducto->nombre);

        $showResponse = $this->actingAs($user)
            ->get(route('trazabilidad.show', ['codigo' => $productoTerminado->numero_lote_produccion]));

        $showResponse->assertOk();
        $showResponse->assertSeeText($productoTerminado->numero_lote_produccion);
    }

    public function test_user_with_approval_permission_can_approve_stage_and_log_event(): void
    {
        [$user, $orden] = $this->crearEscenarioBase();

        $etapa1 = TrazabilidadEtapa::query()->create([
            'orden_produccion_id' => $orden->id,
            'etapa_plantilla_id' => EtapaProduccionPlantilla::query()->value('id'),
            'responsable_id' => $user->id,
            'numero_secuencia' => 1,
            'numero_ejecucion' => 1,
            'fecha_inicio_prevista' => now()->subHour(),
            'fecha_fin_prevista' => now(),
            'estado' => 'En Proceso',
        ]);

        $etapa2 = TrazabilidadEtapa::query()->create([
            'orden_produccion_id' => $orden->id,
            'etapa_plantilla_id' => EtapaProduccionPlantilla::query()->value('id'),
            'responsable_id' => $user->id,
            'numero_secuencia' => 2,
            'numero_ejecucion' => 2,
            'fecha_inicio_prevista' => now(),
            'fecha_fin_prevista' => now()->addHour(),
            'estado' => 'Esperando Aprobación',
        ]);

        $response = $this->actingAs($user)
            ->from(route('trazabilidad.index'))
            ->patch(route('trazabilidad.etapas.aprobar', ['etapaId' => $etapa2->id]));

        $response->assertRedirect(route('trazabilidad.index'));
        $response->assertSessionHas('ok');

        $etapa1->refresh();
        $etapa2->refresh();

        $this->assertSame('Finalizada', $etapa1->estado);
        $this->assertSame('En Proceso', $etapa2->estado);
        $this->assertNotNull($etapa2->fecha_aprobacion);
        $this->assertSame($user->id, (int) $etapa2->aprobado_por);

        $this->assertDatabaseHas('trazabilidad_registros', [
            'trazabilidad_etapa_id' => $etapa2->id,
            'tipo_evento' => 'Aprobacion',
            'estado_nuevo' => 'En Proceso',
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array{0: User, 1: OrdenProduccion, 2: ProductoTerminado}
     */
    private function crearEscenarioBase(): array
    {
        $role = Role::query()->create([
            'nombre' => 'Supervisor Traza',
            'slug' => 'supervisor-traza',
            'nivel_acceso' => 70,
        ]);

        Permission::query()->create([
            'role_id' => $role->id,
            'modulo' => 'Trazabilidad',
            'puede_ver' => true,
            'puede_aprobar' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Usuario Trazabilidad',
            'email' => 'traza-user@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'activo' => true,
            'departamento' => 'produccion',
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Pieza',
            'abreviatura' => 'pz',
            'tipo' => 'Cantidad',
            'activo' => true,
        ]);

        $tipoProducto = TipoProducto::query()->create([
            'nombre' => 'Mochila Trek 60L',
            'slug' => 'mochila-trek-60l',
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::query()->create([
            'nombre' => 'Costura',
            'codigo' => 'ETP-TRAZA-001',
            'tipo_producto_id' => $tipoProducto->id,
            'numero_secuencia' => 1,
            'duracion_estimada_minutos' => 60,
            'cantidad_operarios' => 1,
            'activo' => true,
            'tipo_etapa' => 'produccion',
        ]);

        $orden = OrdenProduccion::query()->create([
            'tipo_producto_id' => $tipoProducto->id,
            'user_id' => $user->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->toDateString(),
            'fecha_fin_prevista' => now()->addDay()->toDateString(),
            'cantidad_produccion' => 10,
            'unidad_medida_id' => $unidad->id,
            'estado' => 'En Proceso',
            'etapas_totales' => 2,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
        ]);

        $productoTerminado = ProductoTerminado::query()->create([
            'numero_lote_produccion' => 'LOTE-TRAZA-001',
            'numero_serie' => 'SER-TRAZA-001',
            'orden_produccion_id' => $orden->id,
            'tipo_producto_id' => $tipoProducto->id,
            'user_responsable_id' => $user->id,
            'fecha_produccion' => now(),
            'fecha_finalizacion' => now(),
            'cantidad_producida' => 10,
            'unidad_medida_id' => $unidad->id,
            'estado' => 'APROBADO',
            'estado_calidad' => 'Aceptada',
            'costo_produccion' => 100,
            'codigo_barras' => 'BAR-TRAZA-001',
            'codigo_qr' => 'QR-TRAZA-001',
        ]);

        return [$user, $orden, $productoTerminado];
    }
}
