<?php

namespace Database\Seeders;

use App\Models\EtapaProduccionPlantilla;
use App\Models\TipoProducto;
use Illuminate\Database\Seeder;

class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $tipoProducto = TipoProducto::where('slug', 'mochila')->first()
            ?? TipoProducto::orderBy('id')->first();

        if (! $tipoProducto) {
            return;
        }

        $etapas = [
            [
                'nombre' => 'Corte',
                'codigo' => 'ETAPA-BASE-001',
                'descripcion' => 'Preparación y corte de materiales según especificación técnica.',
                'numero_secuencia' => 1,
                'tipo_etapa' => 'Corte',
                'duracion_estimada_minutos' => 60,
                'cantidad_operarios' => 2,
                'requiere_validacion' => true,
                'es_etapa_critica' => true,
            ],
            [
                'nombre' => 'Costura',
                'codigo' => 'ETAPA-BASE-002',
                'descripcion' => 'Unión y confección de piezas mediante proceso de costura.',
                'numero_secuencia' => 2,
                'tipo_etapa' => 'Costura',
                'duracion_estimada_minutos' => 90,
                'cantidad_operarios' => 2,
                'requiere_validacion' => false,
                'es_etapa_critica' => true,
            ],
            [
                'nombre' => 'Herrajes',
                'codigo' => 'ETAPA-BASE-003',
                'descripcion' => 'Instalación de hebillas, anclajes y componentes metálicos.',
                'numero_secuencia' => 3,
                'tipo_etapa' => 'Ensamble',
                'duracion_estimada_minutos' => 45,
                'cantidad_operarios' => 1,
                'requiere_validacion' => false,
                'es_etapa_critica' => false,
            ],
            [
                'nombre' => 'Control de Calidad',
                'codigo' => 'ETAPA-BASE-004',
                'descripcion' => 'Inspección final de calidad, funcionalidad y acabados.',
                'numero_secuencia' => 4,
                'tipo_etapa' => 'Inspección',
                'duracion_estimada_minutos' => 30,
                'cantidad_operarios' => 1,
                'requiere_validacion' => true,
                'es_etapa_critica' => true,
            ],
            [
                'nombre' => 'Empaque',
                'codigo' => 'ETAPA-BASE-005',
                'descripcion' => 'Empaque, etiquetado y liberación para almacenamiento o envío.',
                'numero_secuencia' => 5,
                'tipo_etapa' => 'Empaque',
                'duracion_estimada_minutos' => 20,
                'cantidad_operarios' => 1,
                'requiere_validacion' => false,
                'es_etapa_critica' => false,
            ],
        ];

        foreach ($etapas as $etapa) {
            EtapaProduccionPlantilla::updateOrCreate(
                ['codigo' => $etapa['codigo']],
                array_merge($etapa, [
                    'tipo_producto_id' => $tipoProducto->id,
                    'activo' => true,
                ])
            );
        }
    }
}
