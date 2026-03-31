<?php

namespace Database\Seeders;

use App\Models\UbicacionAlmacen;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UbicacionAlmacenSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $ubicaciones = [
            [
                'codigo_ubicacion' => 'A-01-01-01-01',
                'nombre' => 'Edificio A - Piso 1 - Sección 1 - Estante 1 - Nivel 1 (Textiles)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 1,
                'seccion' => 1,
                'estante' => 1,
                'nivel' => 1,
                'capacidad_maxima' => 500.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'A-01-01-02-01',
                'nombre' => 'Edificio A - Piso 1 - Sección 1 - Estante 2 - Nivel 1 (Estructuras)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 1,
                'seccion' => 1,
                'estante' => 2,
                'nivel' => 1,
                'capacidad_maxima' => 300.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'A-01-02-01-01',
                'nombre' => 'Edificio A - Piso 1 - Sección 2 - Estante 1 - Nivel 1 (Herrajes)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 1,
                'seccion' => 2,
                'estante' => 1,
                'nivel' => 1,
                'capacidad_maxima' => 200.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'A-01-03-01-01',
                'nombre' => 'Edificio A - Piso 1 - Sección 3 - Estante 1 - Nivel 1 (Espumados)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 1,
                'seccion' => 3,
                'estante' => 1,
                'nivel' => 1,
                'capacidad_maxima' => 250.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'A-02-01-01-01',
                'nombre' => 'Edificio A - Piso 2 - Sección 1 - Estante 1 - Nivel 1 (Empaque)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 2,
                'seccion' => 1,
                'estante' => 1,
                'nivel' => 1,
                'capacidad_maxima' => 800.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'A-02-02-01-01',
                'nombre' => 'Edificio A - Piso 2 - Sección 2 - Estante 1 - Nivel 1 (Químicos)',
                'tipo' => 'Estantería',
                'edificio' => 'A',
                'piso' => 2,
                'seccion' => 2,
                'estante' => 1,
                'nivel' => 1,
                'capacidad_maxima' => 150.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'B-01-01-01-01',
                'nombre' => 'Edificio B (Productos Terminados) - Piso 1 - Zona 1',
                'tipo' => 'Zona Abierta',
                'edificio' => 'B',
                'piso' => 1,
                'seccion' => 1,
                'estante' => 0,
                'nivel' => 0,
                'capacidad_maxima' => 2000.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
            [
                'codigo_ubicacion' => 'TEMP-01',
                'nombre' => 'Área de Recepción Temporal',
                'tipo' => 'Área Temporales',
                'edificio' => 'A',
                'piso' => 0,
                'seccion' => 0,
                'estante' => 0,
                'nivel' => 0,
                'capacidad_maxima' => 100.0,
                'capacidad_actual' => 0.0,
                'activo' => true,
            ],
        ];

        foreach ($ubicaciones as $ubicacion) {
            UbicacionAlmacen::updateOrCreate(
                ['codigo_ubicacion' => $ubicacion['codigo_ubicacion']],
                $ubicacion
            );
        }
    }
}
