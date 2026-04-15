<?php

namespace Tests\Feature;

use App\Models\CategoriaInsumo;
use App\Models\Insumo;
use App\Models\NotificacionSistema;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bandeja_y_campana_muestran_solo_notificaciones_del_usuario_actual(): void
    {
        $rolAdmin = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $rolOperador = Role::query()->create([
            'nombre' => 'Operador',
            'slug' => 'operador',
            'nivel_acceso' => 10,
        ]);

        $usuario = User::query()->create([
            'name' => 'Admin Notificaciones',
            'email' => 'admin-notificaciones@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        $otroUsuario = User::query()->create([
            'name' => 'Operario Ajeno',
            'email' => 'operario-ajeno@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolOperador->id,
            'activo' => true,
        ]);

        NotificacionSistema::query()->create([
            'titulo' => 'Tarea personal',
            'mensaje' => 'Notificación directa al administrador',
            'tipo' => 'Informativa',
            'modulo' => 'Compras',
            'prioridad' => 'Media',
            'user_id' => $usuario->id,
            'role_id' => $usuario->role_id,
            'estado' => 'Pendiente',
        ]);

        NotificacionSistema::query()->create([
            'titulo' => 'Aviso por rol',
            'mensaje' => 'Notificación general para el rol administrador',
            'tipo' => 'Alerta',
            'modulo' => 'Produccion',
            'prioridad' => 'Alta',
            'user_id' => null,
            'role_id' => $usuario->role_id,
            'estado' => 'Pendiente',
        ]);

        NotificacionSistema::query()->create([
            'titulo' => 'Notificación ajena',
            'mensaje' => 'No debe mostrarse a otro usuario',
            'tipo' => 'Informativa',
            'modulo' => 'Insumos',
            'prioridad' => 'Baja',
            'user_id' => $otroUsuario->id,
            'role_id' => $otroUsuario->role_id,
            'estado' => 'Pendiente',
        ]);

        $response = $this->actingAs($usuario)->get(route('notificaciones.index'));

        $response->assertOk();
        $response->assertSee('Tarea personal');
        $response->assertSee('Aviso por rol');
        $response->assertDontSee('Notificación ajena');
        $response->assertSee('aria-label="Notificaciones (2 pendientes)"', false);
    }

    public function test_resumen_json_devuelve_contador_y_lista_para_actualizacion_automatica(): void
    {
        $rolAdmin = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $usuario = User::query()->create([
            'name' => 'Admin Refresh',
            'email' => 'admin-refresh@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        NotificacionSistema::query()->create([
            'titulo' => 'Pendiente visible',
            'mensaje' => 'Debe salir en el resumen',
            'tipo' => 'Alerta',
            'modulo' => 'Compras',
            'prioridad' => 'Alta',
            'user_id' => $usuario->id,
            'role_id' => $usuario->role_id,
            'estado' => 'Pendiente',
        ]);

        NotificacionSistema::query()->create([
            'titulo' => 'Archivada oculta',
            'mensaje' => 'No debe salir en el contador',
            'tipo' => 'Informativa',
            'modulo' => 'Compras',
            'prioridad' => 'Baja',
            'user_id' => $usuario->id,
            'role_id' => $usuario->role_id,
            'estado' => 'Archivada',
        ]);

        $response = $this->actingAs($usuario)
            ->getJson(route('notificaciones.resumen'));

        $response->assertOk();
        $response->assertJsonPath('pending_count', 1);
        $response->assertJsonCount(1, 'notifications');
        $response->assertJsonPath('notifications.0.titulo', 'Pendiente visible');
    }

    public function test_insumo_con_stock_bajo_visible_en_tablero_se_sincroniza_con_la_campana(): void
    {
        $rolAdmin = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $usuario = User::query()->create([
            'name' => 'Admin Stock',
            'email' => 'admin-stock@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        $unidad = UnidadMedida::query()->create([
            'nombre' => 'Pieza',
            'abreviatura' => 'pz',
            'tipo' => 'Cantidad',
        ]);

        $categoria = CategoriaInsumo::query()->create([
            'nombre' => 'Herrajes',
            'slug' => 'herrajes',
            'activo' => true,
        ]);

        $proveedor = Proveedor::query()->create([
            'razon_social' => 'Herrajes Proveedor SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        Insumo::query()->create([
            'codigo_insumo' => '0003',
            'nombre' => 'fierro',
            'categoria_insumo_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'proveedor_id' => $proveedor->id,
            'stock_actual' => 0,
            'stock_minimo' => 1000,
            'precio_unitario' => 1,
            'estado' => 'Activo',
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('insumos.index'))->assertOk();

        $response = $this->actingAs($usuario)
            ->getJson(route('notificaciones.resumen'));

        $response->assertOk();
        $response->assertJsonPath('pending_count', 1);
        $response->assertJsonPath('notifications.0.modulo', 'Insumos');
        $response->assertJsonPath('notifications.0.prioridad', 'Alta');
    }
}
