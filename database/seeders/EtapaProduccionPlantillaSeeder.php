<?php

namespace Database\Seeders;

use App\Models\EtapaProduccionPlantilla;
use App\Models\TipoProducto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EtapaProduccionPlantillaSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener tipos de producto
        $mochila = TipoProducto::where('slug', 'mochila')->first();
        $carpa = TipoProducto::where('slug', 'carpa')->first();

        if (!$mochila || !$carpa) {
            return;
        }

        // Etapas para Mochila
        EtapaProduccionPlantilla::truncate();

        // Mochila workflow
        EtapaProduccionPlantilla::create([
            'nombre' => 'Corte de Tela',
            'codigo' => 'ETAPA-MOC-001',
            'descripcion' => 'Corte de piezas de tela según patrones',
            'tipo_producto_id' => $mochila->id,
            'numero_secuencia' => 1,
            'tipo_etapa' => 'Corte',
            'duracion_estimada_minutos' => 60,
            'cantidad_operarios' => 2,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Costura de Bolsillos',
            'codigo' => 'ETAPA-MOC-002',
            'descripcion' => 'Costura de bolsillos laterales y frontales',
            'tipo_producto_id' => $mochila->id,
            'numero_secuencia' => 2,
            'tipo_etapa' => 'Costura',
            'duracion_estimada_minutos' => 90,
            'cantidad_operarios' => 2,
            'requiere_validacion' => false,
            'es_etapa_critica' => false,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Ensamble de Correas',
            'codigo' => 'ETAPA-MOC-003',
            'descripcion' => 'Costura de correas y ajuste a la mochila',
            'tipo_producto_id' => $mochila->id,
            'numero_secuencia' => 3,
            'tipo_etapa' => 'Ensamble',
            'duracion_estimada_minutos' => 75,
            'cantidad_operarios' => 1,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Inspección de Calidad',
            'codigo' => 'ETAPA-MOC-004',
            'descripcion' => 'Control de calidad de costuras y resistencia',
            'tipo_producto_id' => $mochila->id,
            'numero_secuencia' => 4,
            'tipo_etapa' => 'Inspección',
            'duracion_estimada_minutos' => 30,
            'cantidad_operarios' => 1,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Empaque Final',
            'codigo' => 'ETAPA-MOC-005',
            'descripcion' => 'Empaque en bolsa y etiquetado',
            'tipo_producto_id' => $mochila->id,
            'numero_secuencia' => 5,
            'tipo_etapa' => 'Empaque',
            'duracion_estimada_minutos' => 20,
            'cantidad_operarios' => 1,
            'requiere_validacion' => false,
            'es_etapa_critica' => false,
            'activo' => true,
        ]);

        // Carpa workflow
        EtapaProduccionPlantilla::create([
            'nombre' => 'Preparación de Varillas',
            'codigo' => 'ETAPA-CAR-001',
            'descripcion' => 'Corte y preparación de varillas de fibra de vidrio',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 1,
            'tipo_etapa' => 'Corte',
            'duracion_estimada_minutos' => 45,
            'cantidad_operarios' => 1,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Armado de Estructura',
            'codigo' => 'ETAPA-CAR-002',
            'descripcion' => 'Montaje de armazón y estructura basica',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 2,
            'tipo_etapa' => 'Ensamble',
            'duracion_estimada_minutos' => 120,
            'cantidad_operarios' => 3,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Colocación de Tela',
            'codigo' => 'ETAPA-CAR-003',
            'descripcion' => 'Instalación y costura de tela protectora',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 3,
            'tipo_etapa' => 'Costura',
            'duracion_estimada_minutos' => 150,
            'cantidad_operarios' => 2,
            'requiere_validacion' => false,
            'es_etapa_critica' => false,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Instalación de Accesorios',
            'codigo' => 'ETAPA-CAR-004',
            'descripcion' => 'Colocación de puertas, ventanas y cremalleras',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 4,
            'tipo_etapa' => 'Ensamble',
            'duracion_estimada_minutos' => 90,
            'cantidad_operarios' => 2,
            'requiere_validacion' => false,
            'es_etapa_critica' => false,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Prueba de Estanqueidad',
            'codigo' => 'ETAPA-CAR-005',
            'descripcion' => 'Prueba de hermeticidad y resistencia al agua',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 5,
            'tipo_etapa' => 'Inspección',
            'duracion_estimada_minutos' => 60,
            'cantidad_operarios' => 1,
            'requiere_validacion' => true,
            'es_etapa_critica' => true,
            'activo' => true,
        ]);

        EtapaProduccionPlantilla::create([
            'nombre' => 'Empaque para Envío',
            'codigo' => 'ETAPA-CAR-006',
            'descripcion' => 'Plegado y empaque compacto de carpa',
            'tipo_producto_id' => $carpa->id,
            'numero_secuencia' => 6,
            'tipo_etapa' => 'Empaque',
            'duracion_estimada_minutos' => 30,
            'cantidad_operarios' => 1,
            'requiere_validacion' => false,
            'es_etapa_critica' => false,
            'activo' => true,
        ]);
    }
}
