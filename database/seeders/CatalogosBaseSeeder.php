<?php

namespace Database\Seeders;

use App\Models\CategoriaMaterial;
use App\Models\UnidadMedida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogosBaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categoriasMaterial = [
            'Textil tecnico',
            'Estructuras y herrajes',
            'Aislantes termicos',
            'Cordinos y cintas',
            'Costura y sellado',
            'Componentes de cocina',
            'Iluminacion y energia',
            'Empaque y etiquetado',
        ];

        $unidadesMedida = [
            ['nombre' => 'Metro', 'abreviatura' => 'm'],
            ['nombre' => 'Unidad', 'abreviatura' => 'und'],
            ['nombre' => 'Rollo', 'abreviatura' => 'rollo'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'Litro', 'abreviatura' => 'l'],
            ['nombre' => 'Par', 'abreviatura' => 'par'],
            ['nombre' => 'Caja', 'abreviatura' => 'caja'],
        ];

        foreach ($categoriasMaterial as $nombre) {
            CategoriaMaterial::firstOrCreate(['nombre' => $nombre]);
        }

        foreach ($unidadesMedida as $unidad) {
            UnidadMedida::updateOrCreate(
                ['nombre' => $unidad['nombre']],
                ['abreviatura' => $unidad['abreviatura']]
            );
        }
    }
}
