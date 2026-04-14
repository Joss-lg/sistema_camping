<?php

namespace Tests\Feature;

use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrdenCompraNumeroOrdenTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_numero_orden_es_diario_secuencial(): void
    {
        Carbon::setTestNow('2026-04-10 09:00:00');

        [$proveedor, $usuario] = $this->crearDependenciasBase();

        $ordenUno = OrdenCompra::query()->create([
            'proveedor_id' => $proveedor->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Pendiente',
            'total_items' => 0,
            'total_cantidad' => 0,
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
        ]);

        $ordenDos = OrdenCompra::query()->create([
            'proveedor_id' => $proveedor->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Pendiente',
            'total_items' => 0,
            'total_cantidad' => 0,
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
        ]);

        $this->assertSame('OC-20260410-0001', $ordenUno->numero_orden);
        $this->assertSame('OC-20260410-0002', $ordenDos->numero_orden);
    }

    public function test_numero_orden_reinicia_secuencia_cada_dia(): void
    {
        Carbon::setTestNow('2026-04-10 23:59:00');

        [$proveedor, $usuario] = $this->crearDependenciasBase();

        OrdenCompra::query()->create([
            'proveedor_id' => $proveedor->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Pendiente',
            'total_items' => 0,
            'total_cantidad' => 0,
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
        ]);

        Carbon::setTestNow('2026-04-11 08:00:00');

        $ordenSiguienteDia = OrdenCompra::query()->create([
            'proveedor_id' => $proveedor->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Pendiente',
            'total_items' => 0,
            'total_cantidad' => 0,
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
        ]);

        $this->assertSame('OC-20260411-0001', $ordenSiguienteDia->numero_orden);
    }

    /**
     * @return array{0: Proveedor, 1: User}
     */
    private function crearDependenciasBase(): array
    {
        $rol = Role::query()->create([
            'nombre' => 'Super Administrador',
            'slug' => 'super_admin',
            'nivel_acceso' => 100,
        ]);

        $usuario = User::query()->create([
            'name' => 'Usuario Compras Test',
            'email' => 'compras-test@example.test',
            'password' => bcrypt('secret123'),
            'role_id' => $rol->id,
            'activo' => true,
        ]);

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => 'PROV-NUM-001',
            'razon_social' => 'Proveedor Numeracion SA',
            'tipo_proveedor' => 'Materia Prima',
            'estatus' => 'Activo',
        ]);

        return [$proveedor, $usuario];
    }
}
