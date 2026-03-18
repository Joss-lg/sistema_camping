<?php

namespace Database\Seeders;

use App\Models\CategoriaMaterial;
use App\Models\CategoriaProducto;
use App\Models\Estado;
use App\Models\Material;
use App\Models\ProductoTerminado;
use App\Models\RecetaMaterial;
use App\Models\UnidadMedida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogoCampingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $estadoActivoProducto = Estado::firstOrCreate([
            'nombre' => 'Activo',
            'tipo' => 'producto',
        ]);

        $categoriasProducto = [
            'Carpas y refugio',
            'Descanso y abrigo',
            'Cocina outdoor',
            'Iluminacion y energia',
            'Hidratacion y transporte',
        ];

        foreach ($categoriasProducto as $nombreCategoria) {
            CategoriaProducto::firstOrCreate(['nombre' => $nombreCategoria]);
        }

        $materialesBase = [
            [
                'nombre' => 'Tela ripstop impermeable 210D',
                'categoria_material' => 'Textil tecnico',
                'unidad' => 'Metro',
                'stock' => 320,
                'stock_minimo' => 120,
                'stock_maximo' => 600,
            ],
            [
                'nombre' => 'Varilla aluminio 8.5 mm',
                'categoria_material' => 'Estructuras y herrajes',
                'unidad' => 'Unidad',
                'stock' => 420,
                'stock_minimo' => 150,
                'stock_maximo' => 700,
            ],
            [
                'nombre' => 'Aislante EVA alta densidad',
                'categoria_material' => 'Aislantes termicos',
                'unidad' => 'Metro',
                'stock' => 180,
                'stock_minimo' => 70,
                'stock_maximo' => 350,
            ],
            [
                'nombre' => 'Cordino nylon 4 mm',
                'categoria_material' => 'Cordinos y cintas',
                'unidad' => 'Metro',
                'stock' => 500,
                'stock_minimo' => 180,
                'stock_maximo' => 900,
            ],
            [
                'nombre' => 'Valvula de hidratacion TPU',
                'categoria_material' => 'Hidratacion y transporte',
                'unidad' => 'Unidad',
                'stock' => 260,
                'stock_minimo' => 90,
                'stock_maximo' => 480,
            ],
            [
                'nombre' => 'Quemador acero inoxidable',
                'categoria_material' => 'Componentes de cocina',
                'unidad' => 'Unidad',
                'stock' => 130,
                'stock_minimo' => 45,
                'stock_maximo' => 260,
            ],
            [
                'nombre' => 'Modulo LED recargable',
                'categoria_material' => 'Iluminacion y energia',
                'unidad' => 'Unidad',
                'stock' => 210,
                'stock_minimo' => 80,
                'stock_maximo' => 360,
            ],
        ];

        $materialesPorNombre = [];
        foreach ($materialesBase as $materialData) {
            $categoria = CategoriaMaterial::firstOrCreate([
                'nombre' => $materialData['categoria_material'],
            ]);

            $unidad = UnidadMedida::where('nombre', $materialData['unidad'])->first();
            if (! $unidad) {
                continue;
            }

            $material = Material::updateOrCreate(
                ['nombre' => $materialData['nombre']],
                [
                    'categoria_id' => $categoria->id,
                    'unidad_id' => $unidad->id,
                    'stock' => $materialData['stock'],
                    'stock_minimo' => $materialData['stock_minimo'],
                    'stock_maximo' => $materialData['stock_maximo'],
                ]
            );

            $materialesPorNombre[$material->nombre] = $material;
        }

        $productos = [
            [
                'nombre' => 'Carpa Trek 2P Cuatro estaciones',
                'sku' => 'CAMP-CARPA-2P-4S',
                'categoria_producto' => 'Carpas y refugio',
                'unidad' => 'Unidad',
                'stock' => 18,
                'stock_minimo' => 8,
                'stock_maximo' => 45,
                'precio_venta' => 189.90,
                'receta' => [
                    ['material' => 'Tela ripstop impermeable 210D', 'cantidad_base' => 3.8500, 'merma' => 6.00],
                    ['material' => 'Varilla aluminio 8.5 mm', 'cantidad_base' => 3.0000, 'merma' => 2.00],
                    ['material' => 'Cordino nylon 4 mm', 'cantidad_base' => 5.5000, 'merma' => 3.00],
                ],
            ],
            [
                'nombre' => 'Colchoneta termica Sierra R3',
                'sku' => 'CAMP-COLCH-R3',
                'categoria_producto' => 'Descanso y abrigo',
                'unidad' => 'Unidad',
                'stock' => 26,
                'stock_minimo' => 10,
                'stock_maximo' => 60,
                'precio_venta' => 74.50,
                'receta' => [
                    ['material' => 'Aislante EVA alta densidad', 'cantidad_base' => 1.8000, 'merma' => 4.50],
                    ['material' => 'Tela ripstop impermeable 210D', 'cantidad_base' => 0.6500, 'merma' => 4.00],
                ],
            ],
            [
                'nombre' => 'Hornillo compact gas H1',
                'sku' => 'CAMP-HORNILLO-H1',
                'categoria_producto' => 'Cocina outdoor',
                'unidad' => 'Unidad',
                'stock' => 14,
                'stock_minimo' => 6,
                'stock_maximo' => 35,
                'precio_venta' => 59.90,
                'receta' => [
                    ['material' => 'Quemador acero inoxidable', 'cantidad_base' => 1.0000, 'merma' => 1.50],
                    ['material' => 'Cordino nylon 4 mm', 'cantidad_base' => 0.3000, 'merma' => 3.00],
                ],
            ],
            [
                'nombre' => 'Linterna frontal Trail 300',
                'sku' => 'CAMP-LINTERNA-300',
                'categoria_producto' => 'Iluminacion y energia',
                'unidad' => 'Unidad',
                'stock' => 22,
                'stock_minimo' => 9,
                'stock_maximo' => 55,
                'precio_venta' => 39.90,
                'receta' => [
                    ['material' => 'Modulo LED recargable', 'cantidad_base' => 1.0000, 'merma' => 1.00],
                    ['material' => 'Cordino nylon 4 mm', 'cantidad_base' => 0.2500, 'merma' => 3.00],
                ],
            ],
            [
                'nombre' => 'Bolsa hidratacion Alpine 2L',
                'sku' => 'CAMP-HIDRA-2L',
                'categoria_producto' => 'Hidratacion y transporte',
                'unidad' => 'Unidad',
                'stock' => 30,
                'stock_minimo' => 12,
                'stock_maximo' => 70,
                'precio_venta' => 34.90,
                'receta' => [
                    ['material' => 'Valvula de hidratacion TPU', 'cantidad_base' => 1.0000, 'merma' => 1.00],
                    ['material' => 'Tela ripstop impermeable 210D', 'cantidad_base' => 0.9000, 'merma' => 4.00],
                ],
            ],
        ];

        foreach ($productos as $productoData) {
            $categoriaProducto = CategoriaProducto::where('nombre', $productoData['categoria_producto'])->first();
            $unidad = UnidadMedida::where('nombre', $productoData['unidad'])->first();

            if (! $categoriaProducto || ! $unidad) {
                continue;
            }

            $producto = ProductoTerminado::updateOrCreate(
                ['sku' => $productoData['sku']],
                [
                    'nombre' => $productoData['nombre'],
                    'categoria_id' => $categoriaProducto->id,
                    'unidad_id' => $unidad->id,
                    'stock' => $productoData['stock'],
                    'stock_minimo' => $productoData['stock_minimo'],
                    'stock_maximo' => $productoData['stock_maximo'],
                    'precio_venta' => $productoData['precio_venta'],
                    'estado_id' => $estadoActivoProducto->id,
                ]
            );

            foreach ($productoData['receta'] as $lineaReceta) {
                $material = $materialesPorNombre[$lineaReceta['material']] ?? null;
                if (! $material) {
                    continue;
                }

                RecetaMaterial::updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'material_id' => $material->id,
                    ],
                    [
                        'cantidad_base' => $lineaReceta['cantidad_base'],
                        'merma_porcentaje' => $lineaReceta['merma'],
                        'activo' => true,
                    ]
                );
            }
        }
    }
}
