<?php

namespace Database\Seeders;

use App\Models\Insumo;
use App\Models\CategoriaInsumo;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\TipoProducto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InsumoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Get required references
        $categoriaTextiles = CategoriaInsumo::where('slug', 'telas-impermeables')->first();
        $categoriaEstructuras = CategoriaInsumo::where('slug', 'varillas-de-fibra-de-vidrio')->first();
        $categoriaHerrajes = CategoriaInsumo::where('slug', 'hebillas-y-cierres')->first();

        $unidadMetro = UnidadMedida::where('abreviatura', 'm')->first();
        $unidadKg = UnidadMedida::where('abreviatura', 'kg')->first();
        $unidadPz = UnidadMedida::where('abreviatura', 'pz')->first();
        $unidadLitro = UnidadMedida::where('abreviatura', 'L')->first();

        $proveedorTMT = Proveedor::where('codigo_proveedor', 'PROV-001')->first();
        $proveedorMEN = Proveedor::where('codigo_proveedor', 'PROV-002')->first();
        $proveedorHPI = Proveedor::where('codigo_proveedor', 'PROV-003')->first();

        $tipoMochila = TipoProducto::where('slug', 'mochila')->first();
        $tipoCarpa = TipoProducto::where('slug', 'carpa')->first();

        $insumos = [
            // Textiles
            [
                'codigo_insumo' => 'INS-001',
                'nombre' => 'Tela Impermeable Ripstop 100D',
                'descripcion' => 'Tela seda sintética con tratamiento impermeable',
                'categoria_insumo_id' => $categoriaTextiles?->id,
                'unidad_medida_id' => $unidadMetro?->id,
                'tipo_producto_id' => $tipoMochila?->id,
                'stock_minimo' => 50,
                'stock_actual' => 150,
                'proveedor_id' => $proveedorTMT?->id,
                'precio_unitario' => 45.5,
                'precio_costo' => 32.0,
                'estado' => 'Activo',
                'activo' => true,
            ],
            [
                'codigo_insumo' => 'INS-002',
                'nombre' => 'Nylon 210D Naranja Fluorescente',
                'descripcion' => 'Tela de nylon de alto estirado para detalles',
                'categoria_insumo_id' => $categoriaTextiles?->id,
                'unidad_medida_id' => $unidadMetro?->id,
                'tipo_producto_id' => $tipoCarpa?->id,
                'stock_minimo' => 30,
                'stock_actual' => 80,
                'proveedor_id' => $proveedorTMT?->id,
                'precio_unitario' => 25.75,
                'precio_costo' => 18.0,
                'estado' => 'Activo',
                'activo' => true,
            ],
            // Estructuras
            [
                'codigo_insumo' => 'INS-003',
                'nombre' => 'Varilla Fibra de Vidrio 7mm',
                'descripcion' => 'Varillas para marcos estructurales de carpas',
                'categoria_insumo_id' => $categoriaEstructuras?->id,
                'unidad_medida_id' => $unidadMetro?->id,
                'tipo_producto_id' => $tipoCarpa?->id,
                'stock_minimo' => 100,
                'stock_actual' => 250,
                'proveedor_id' => $proveedorMEN?->id,
                'precio_unitario' => 8.5,
                'precio_costo' => 6.0,
                'estado' => 'Activo',
                'activo' => true,
            ],
            [
                'codigo_insumo' => 'INS-004',
                'nombre' => 'Tubo Aluminio 16x16mm',
                'descripcion' => 'Tubería cuadrada de aluminio para estructuras principales',
                'categoria_insumo_id' => $categoriaEstructuras?->id,
                'unidad_medida_id' => $unidadMetro?->id,
                'tipo_producto_id' => $tipoCarpa?->id,
                'stock_minimo' => 50,
                'stock_actual' => 120,
                'proveedor_id' => $proveedorMEN?->id,
                'precio_unitario' => 12.5,
                'precio_costo' => 9.0,
                'estado' => 'Activo',
                'activo' => true,
            ],
            // Herrajes
            [
                'codigo_insumo' => 'INS-005',
                'nombre' => 'Hebilla Plástica Regulable 25mm',
                'descripcion' => 'Hebillas de cierre rápido para correas y cinturones',
                'categoria_insumo_id' => $categoriaHerrajes?->id,
                'unidad_medida_id' => $unidadPz?->id,
                'tipo_producto_id' => $tipoMochila?->id,
                'stock_minimo' => 500,
                'stock_actual' => 2000,
                'proveedor_id' => $proveedorHPI?->id,
                'precio_unitario' => 0.85,
                'precio_costo' => 0.50,
                'estado' => 'Activo',
                'activo' => true,
            ],
            [
                'codigo_insumo' => 'INS-006',
                'nombre' => 'D-Ring Metálico 20mm',
                'descripcion' => 'Anillo D de metal para puntos de anclaje',
                'categoria_insumo_id' => $categoriaHerrajes?->id,
                'unidad_medida_id' => $unidadPz?->id,
                'tipo_producto_id' => $tipoMochila?->id,
                'stock_minimo' => 300,
                'stock_actual' => 1200,
                'proveedor_id' => $proveedorHPI?->id,
                'precio_unitario' => 0.65,
                'precio_costo' => 0.40,
                'estado' => 'Activo',
                'activo' => true,
            ],
        ];

        foreach ($insumos as $insumo) {
            if ($insumo['categoria_insumo_id'] && $insumo['unidad_medida_id'] && $insumo['proveedor_id']) {
                Insumo::updateOrCreate(
                    ['codigo_insumo' => $insumo['codigo_insumo']],
                    $insumo
                );
            }
        }
    }
}
