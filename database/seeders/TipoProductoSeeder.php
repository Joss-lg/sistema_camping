<?php

namespace Database\Seeders;

use App\Models\TipoProducto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TipoProductoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'Mochila',
                'descripcion' => 'Mochilas de excursión y camping',
                'icono' => '🎒',
                'color' => '#FF6B6B',
                'activo' => true,
            ],
            [
                'nombre' => 'Carpa',
                'descripcion' => 'Refugios portátiles para acampada',
                'icono' => '⛺',
                'color' => '#4ECDC4',
                'activo' => true,
            ],
            [
                'nombre' => 'Sleeping Bag',
                'descripcion' => 'Bolsas de dormir para diferentes climas',
                'icono' => '🛏️',
                'color' => '#45B7D1',
                'activo' => true,
            ],
            [
                'nombre' => 'Accesorios',
                'descripcion' => 'Complementos y equipamiento auxiliar',
                'icono' => '🧭',
                'color' => '#FFA07A',
                'activo' => true,
            ],
            [
                'nombre' => 'Equipo de Cocina',
                'descripcion' => 'Utensilios y equipo para preparación de alimentos',
                'icono' => '🍳',
                'color' => '#F4A460',
                'activo' => true,
            ],
            [
                'nombre' => 'Sistema de Iluminación',
                'descripcion' => 'Linternas, lámparas y sistemas LED',
                'icono' => '💡',
                'color' => '#FFD700',
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoProducto::updateOrCreate(
                ['slug' => Str::slug($tipo['nombre'])],
                $tipo
            );
        }
    }
}
