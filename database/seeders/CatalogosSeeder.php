<?php

namespace Database\Seeders;

use App\Models\CategoriaInsumo;
use App\Models\TipoProducto;
use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['nombre' => 'Metro', 'abreviatura' => 'm', 'tipo' => 'Longitud', 'factor_conversion_base' => 1.0, 'activo' => true],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'tipo' => 'Peso', 'factor_conversion_base' => 1.0, 'activo' => true],
            ['nombre' => 'Pieza', 'abreviatura' => 'pz', 'tipo' => 'Conteo', 'factor_conversion_base' => 1.0, 'activo' => true],
            ['nombre' => 'Litro', 'abreviatura' => 'L', 'tipo' => 'Volumen', 'factor_conversion_base' => 1.0, 'activo' => true],
            ['nombre' => 'Rollo', 'abreviatura' => 'rl', 'tipo' => 'Conteo', 'factor_conversion_base' => 1.0, 'activo' => true],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::updateOrCreate(
                ['abreviatura' => $unidad['abreviatura']],
                $unidad
            );
        }

        $categorias = [
            ['nombre' => 'Textiles', 'descripcion' => 'Telas y materiales textiles para productos de camping'],
            ['nombre' => 'Estructuras y Elementos Rígidos', 'descripcion' => 'Varillas, tubos y piezas de soporte estructural'],
            ['nombre' => 'Herrajes y Accesorios Metálicos', 'descripcion' => 'Componentes de ajuste, unión y sujeción'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaInsumo::updateOrCreate(
                ['slug' => Str::slug($categoria['nombre'])],
                [
                    'nombre' => $categoria['nombre'],
                    'slug' => Str::slug($categoria['nombre']),
                    'descripcion' => $categoria['descripcion'],
                    'categoria_padre_id' => null,
                    'activo' => true,
                ]
            );
        }

        $subcategorias = [
            ['nombre' => 'Telas Impermeables', 'descripcion' => 'Materiales con protección contra humedad', 'padre_slug' => 'textiles'],
            ['nombre' => 'Varillas de Fibra de Vidrio', 'descripcion' => 'Varillas ligeras para estructura de carpas', 'padre_slug' => 'estructuras-y-elementos-rigidos'],
            ['nombre' => 'Hebillas y Cierres', 'descripcion' => 'Sistemas de cierre rápido y ajuste', 'padre_slug' => 'herrajes-y-accesorios-metalicos'],
        ];

        foreach ($subcategorias as $subcategoria) {
            $categoriaPadre = CategoriaInsumo::where('slug', $subcategoria['padre_slug'])->first();

            if (! $categoriaPadre) {
                continue;
            }

            CategoriaInsumo::updateOrCreate(
                ['slug' => Str::slug($subcategoria['nombre'])],
                [
                    'nombre' => $subcategoria['nombre'],
                    'slug' => Str::slug($subcategoria['nombre']),
                    'descripcion' => $subcategoria['descripcion'],
                    'categoria_padre_id' => $categoriaPadre->id,
                    'activo' => true,
                ]
            );
        }

        $tiposProducto = [
            ['nombre' => 'Mochila', 'descripcion' => 'Mochilas técnicas para excursión y montaña', 'icono' => '🎒', 'color' => '#FF6B6B'],
            ['nombre' => 'Carpa', 'descripcion' => 'Refugios portátiles para acampada familiar y expedición', 'icono' => '⛺', 'color' => '#4ECDC4'],
            ['nombre' => 'Sleeping Bag', 'descripcion' => 'Bolsas de dormir para distintos rangos térmicos', 'icono' => '🛏️', 'color' => '#45B7D1'],
            ['nombre' => 'Accesorios', 'descripcion' => 'Complementos funcionales para actividades outdoor', 'icono' => '🧭', 'color' => '#FFA07A'],
        ];

        foreach ($tiposProducto as $tipoProducto) {
            TipoProducto::updateOrCreate(
                ['slug' => Str::slug($tipoProducto['nombre'])],
                [
                    'nombre' => $tipoProducto['nombre'],
                    'slug' => Str::slug($tipoProducto['nombre']),
                    'descripcion' => $tipoProducto['descripcion'],
                    'icono' => $tipoProducto['icono'],
                    'color' => $tipoProducto['color'],
                    'activo' => true,
                ]
            );
        }
    }
}
