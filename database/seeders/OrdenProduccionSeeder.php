<?php

namespace Database\Seeders;

use App\Models\OrdenProduccion;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrdenProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::whereIn('email', [
            'admin@correo.com',
            'admin@camping.local',
            'admin@logicamp.local',
        ])->first();
        $unidadMedida = UnidadMedida::where('abreviatura', 'pz')->first();
        $mochila = TipoProducto::where('slug', 'mochila')->first();
        $carpa = TipoProducto::where('slug', 'carpa')->first();

        if (!$usuario || !$unidadMedida || !$mochila || !$carpa) {
            return;
        }

        // Orden 1: Mochila
        OrdenProduccion::updateOrCreate([
            'numero_orden' => 'OP-2026-001',
        ], [
            'numero_orden' => 'OP-2026-001',
            'tipo_producto_id' => $mochila->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->addDays(1),
            'fecha_fin_prevista' => now()->addDays(3),
            'cantidad_produccion' => 50,
            'unidad_medida_id' => $unidadMedida->id,
            'estado' => 'Pendiente',
            'etapas_totales' => 5,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
            'costo_estimado' => 2500.00,
            'prioridad' => 'Alta',
            'requiere_calidad' => true,
            'notas' => 'Pedido para cliente ChoaMarks por temporada alta',
        ]);

        // Orden 2: Carpa
        OrdenProduccion::updateOrCreate([
            'numero_orden' => 'OP-2026-002',
        ], [
            'numero_orden' => 'OP-2026-002',
            'tipo_producto_id' => $carpa->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now()->addDays(2),
            'fecha_fin_prevista' => now()->addDays(8),
            'cantidad_produccion' => 20,
            'unidad_medida_id' => $unidadMedida->id,
            'estado' => 'Pendiente',
            'etapas_totales' => 6,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
            'costo_estimado' => 12000.00,
            'prioridad' => 'Media',
            'requiere_calidad' => true,
            'notas' => 'Producción estándar para inventario',
        ]);

        // Orden 3: Mochila (expedita)
        OrdenProduccion::updateOrCreate([
            'numero_orden' => 'OP-2026-003',
        ], [
            'numero_orden' => 'OP-2026-003',
            'tipo_producto_id' => $mochila->id,
            'user_id' => $usuario->id,
            'fecha_orden' => now(),
            'fecha_inicio_prevista' => now(),
            'fecha_fin_prevista' => now()->addDays(1),
            'cantidad_produccion' => 25,
            'unidad_medida_id' => $unidadMedida->id,
            'estado' => 'Pendiente',
            'etapas_totales' => 5,
            'etapas_completadas' => 0,
            'porcentaje_completado' => 0,
            'costo_estimado' => 1500.00,
            'prioridad' => 'Urgente',
            'requiere_calidad' => true,
            'especificaciones_especiales' => 'Color naranja adicional, refuerzos extras',
            'notas' => 'Orden expedita - cliente especial',
        ]);
    }
}
