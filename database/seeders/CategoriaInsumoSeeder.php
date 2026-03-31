<?php

namespace Database\Seeders;

use App\Models\CategoriaInsumo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaInsumoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Categorías principales (sin padre)
        $categoriasPrincipales = [
            [
                'nombre' => 'Textiles',
                'descripcion' => 'Telas y materiales textiles',
                'activo' => true,
            ],
            [
                'nombre' => 'Estructuras y Elementos Rígidos',
                'descripcion' => 'Varillas, tubos y marcos estructurales',
                'activo' => true,
            ],
            [
                'nombre' => 'Herrajes y Accesorios Metálicos',
                'descripcion' => 'Hebillas, ganchos, remaches y fijaciones',
                'activo' => true,
            ],
            [
                'nombre' => 'Espumados y Rellenos',
                'descripcion' => 'Materiales de acolchado y aislamiento',
                'activo' => true,
            ],
            [
                'nombre' => 'Empaque y Materiales de Envío',
                'descripcion' => 'Cajas, papeles y materiales de protección',
                'activo' => true,
            ],
            [
                'nombre' => 'Químicos y Adhesivos',
                'descripcion' => 'Pegamentos, selladores y tratamientos',
                'activo' => true,
            ],
        ];

        $subcategorias = [
            'Textiles' => [
                ['nombre' => 'Telas Impermeables', 'descripcion' => 'Materiales resistentes al agua'],
                ['nombre' => 'Telas de Alto Estirado', 'descripcion' => 'Nylon y poliéster reforzado'],
                ['nombre' => 'Lienzos Naturales', 'descripcion' => 'Algodón y mezclas naturales'],
            ],
            'Estructuras y Elementos Rígidos' => [
                ['nombre' => 'Varillas de Fibra de Vidrio', 'descripcion' => 'Para estructuras ligeras'],
                ['nombre' => 'Tubos de Aluminio', 'descripcion' => 'Elementos estructurales principales'],
                ['nombre' => 'Polos y Soportes', 'descripcion' => 'Componentes para carpas'],
            ],
            'Herrajes y Accesorios Metálicos' => [
                ['nombre' => 'Hebillas y Cierres', 'descripcion' => 'Sistemas de cierre y ajuste'],
                ['nombre' => 'D-Rings y Puntos de Anclaje', 'descripcion' => 'Para sistemas de sujeción'],
                ['nombre' => 'Remaches y Pernos', 'descripcion' => 'Fijaciones permanentes'],
            ],
            'Espumados y Rellenos' => [
                ['nombre' => 'Espuma EVA', 'descripcion' => 'Aislamiento térmico'],
                ['nombre' => 'Fibra Hueca', 'descripcion' => 'Relleno para bolsas de dormir'],
                ['nombre' => 'Espuma de Poliuretano', 'descripcion' => 'Amortiguación y confort'],
            ],
            'Empaque y Materiales de Envío' => [
                ['nombre' => 'Cajas Corrugadas', 'descripcion' => 'Empaques de transporte'],
                ['nombre' => 'Papel y Cartón', 'descripcion' => 'Materiales de relleno'],
                ['nombre' => 'Bolsas de Plástico', 'descripcion' => 'Protección de productos'],
            ],
            'Químicos y Adhesivos' => [
                ['nombre' => 'Pegamentos Textiles', 'descripcion' => 'Adhesivos para telas'],
                ['nombre' => 'Selladores Impermeables', 'descripcion' => 'Tratamientos antihumedad'],
                ['nombre' => 'Tinturas y Tintas', 'descripcion' => 'Para acabados y decoración'],
            ],
        ];

        // Insertar categorías principales
        foreach ($categoriasPrincipales as $categoria) {
            CategoriaInsumo::updateOrCreate(
                ['slug' => Str::slug($categoria['nombre'])],
                array_merge($categoria, ['categoria_padre_id' => null])
            );
        }

        // Insertar subcategorías
        foreach ($subcategorias as $categoriaPadre => $subcat) {
            $padre = CategoriaInsumo::where('nombre', $categoriaPadre)->first();

            if ($padre) {
                foreach ($subcat as $sub) {
                    CategoriaInsumo::updateOrCreate(
                        ['slug' => Str::slug($sub['nombre'])],
                        array_merge($sub, [
                            'categoria_padre_id' => $padre->id,
                            'activo' => true,
                        ])
                    );
                }
            }
        }
    }
}
