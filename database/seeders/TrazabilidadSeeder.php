<?php

namespace Database\Seeders;

use App\Models\ConsumoMaterial;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\TrazabilidadEtapa;
use App\Models\TrazabilidadRegistro;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrazabilidadSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::whereIn('email', [
            'admin@correo.com',
            'admin@camping.local',
            'admin@logicamp.local',
        ])->first();
        if (!$usuario) {
            return;
        }

        // Obtener una orden de prueba
        $orden = OrdenProduccion::where('numero_orden', 'OP-2026-001')->first();
        if (!$orden) {
            return;
        }

        // Limpiar datos relacionados con la orden de prueba sin truncar tablas con FKs.
        $etapasExistentesIds = TrazabilidadEtapa::withTrashed()
            ->where('orden_produccion_id', $orden->id)
            ->pluck('id');

        if ($etapasExistentesIds->isNotEmpty()) {
            TrazabilidadRegistro::whereIn('trazabilidad_etapa_id', $etapasExistentesIds)->delete();
        }

        TrazabilidadEtapa::withTrashed()->where('orden_produccion_id', $orden->id)->forceDelete();
        ConsumoMaterial::where('orden_produccion_id', $orden->id)->delete();
        ProductoTerminado::withTrashed()
            ->where('orden_produccion_id', $orden->id)
            ->forceDelete();

        // Crear etapas de trazabilidad para la orden
        $etapas = $orden->tipoProducto->etapasProduccionPlantilla()->get();

        foreach ($etapas as $index => $etapa) {
            $trazabilidad = TrazabilidadEtapa::updateOrCreate(
                [
                    'orden_produccion_id' => $orden->id,
                    'etapa_plantilla_id' => $etapa->id,
                    'numero_ejecucion' => 1,
                ],
                [
                'numero_secuencia' => $etapa->numero_secuencia,
                'numero_ejecucion' => 1,
                'fecha_inicio_prevista' => now()->addHours($index),
                'fecha_fin_prevista' => now()->addHours($index + 2),
                'estado' => $index === 0 ? 'En Proceso' : 'Pendiente',
                'cantidad_operarios' => $etapa->cantidad_operarios,
                'operarios_asignados' => 'Juan García, María López',
                ]
            );

            // Registros de trazabilidad para la primera etapa
            if ($index === 0) {
                TrazabilidadRegistro::create([
                    'trazabilidad_etapa_id' => $trazabilidad->id,
                    'orden_produccion_id' => $orden->id,
                    'user_id' => $usuario->id,
                    'tipo_evento' => 'Inicio',
                    'estado_anterior' => 'Pendiente',
                    'estado_nuevo' => 'En Proceso',
                    'descripcion_evento' => 'Se inició la etapa de ' . $etapa->nombre,
                    'fecha_evento' => now(),
                    'dispositivo_registro' => 'Estación de Trabajo 1',
                ]);

                TrazabilidadRegistro::create([
                    'trazabilidad_etapa_id' => $trazabilidad->id,
                    'orden_produccion_id' => $orden->id,
                    'user_id' => $usuario->id,
                    'tipo_evento' => 'Observacion',
                    'estado_anterior' => 'En Proceso',
                    'estado_nuevo' => 'En Proceso',
                    'descripcion_evento' => 'Todos los insumos disponibles y verificados',
                    'fecha_evento' => now()->addMinutes(15),
                    'dispositivo_registro' => 'Estación de Trabajo 1',
                ]);
            }
        }

        // Crear algunos productos terminados de muestra
        $productoTerminado = ProductoTerminado::create([
            'numero_lote_produccion' => 'LOTE-MOC-001-001',
            'numero_serie' => 'S001-' . time(),
            'orden_produccion_id' => $orden->id,
            'tipo_producto_id' => $orden->tipo_producto_id,
            'user_responsable_id' => $usuario->id,
            'fecha_produccion' => now(),
            'cantidad_producida' => 1,
            'unidad_medida_id' => $orden->unidad_medida_id,
            'estado' => 'Aprobado',
            'estado_calidad' => 'Aceptada',
            'costo_produccion' => 50.00,
            'notas' => 'Producto de muestra',
        ]);

        // Crear consumos de muestra
        $insumoTela = Insumo::where('codigo_insumo', 'INS-001')->first();
        if ($insumoTela) {
            $lote = LoteInsumo::where('insumo_id', $insumoTela->id)->first();
            if ($lote) {
                ConsumoMaterial::create([
                    'orden_produccion_id' => $orden->id,
                    'insumo_id' => $insumoTela->id,
                    'lote_insumo_id' => $lote->id,
                    'unidad_medida_id' => $insumoTela->unidad_medida_id,
                    'cantidad_consumida' => 2.5,
                    'cantidad_desperdicio' => 0.5,
                    'user_id' => $usuario->id,
                    'fecha_consumo' => now(),
                    'estado_material' => 'Conforme',
                    'numero_lote_produccion' => 'LOTE-MOC-001-001',
                ]);
            }
        }
    }
}
