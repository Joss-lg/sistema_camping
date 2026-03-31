<?php

namespace Database\Seeders;

use App\Models\UnidadMedida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnidadMedidaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $unidades = [
            [
                'nombre' => 'Metro',
                'abreviatura' => 'm',
                'tipo' => 'Longitud',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Centímetro',
                'abreviatura' => 'cm',
                'tipo' => 'Longitud',
                'factor_conversion_base' => 0.01,
                'activo' => true,
            ],
            [
                'nombre' => 'Milímetro',
                'abreviatura' => 'mm',
                'tipo' => 'Longitud',
                'factor_conversion_base' => 0.001,
                'activo' => true,
            ],
            [
                'nombre' => 'Kilogramo',
                'abreviatura' => 'kg',
                'tipo' => 'Peso',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Gramo',
                'abreviatura' => 'g',
                'tipo' => 'Peso',
                'factor_conversion_base' => 0.001,
                'activo' => true,
            ],
            [
                'nombre' => 'Litro',
                'abreviatura' => 'L',
                'tipo' => 'Volumen',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Mililitro',
                'abreviatura' => 'mL',
                'tipo' => 'Volumen',
                'factor_conversion_base' => 0.001,
                'activo' => true,
            ],
            [
                'nombre' => 'Pieza',
                'abreviatura' => 'pz',
                'tipo' => 'Conteo',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Docena',
                'abreviatura' => 'dz',
                'tipo' => 'Conteo',
                'factor_conversion_base' => 12.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Metro Cuadrado',
                'abreviatura' => 'm²',
                'tipo' => 'Área',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Metro Cúbico',
                'abreviatura' => 'm³',
                'tipo' => 'Volumen',
                'factor_conversion_base' => 1000.0,
                'activo' => true,
            ],
            [
                'nombre' => 'Rollo',
                'abreviatura' => 'rl',
                'tipo' => 'Conteo',
                'factor_conversion_base' => 1.0,
                'activo' => true,
            ],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::updateOrCreate(
                ['abreviatura' => $unidad['abreviatura']],
                $unidad
            );
        }
    }
}
