<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Mantener activo solo el seeder de usuarios.
            UserSeeder::class,

            // Seeders comentados para evitar carga de datos demo:
            // SecuritySeeder::class,
            // CatalogosSeeder::class,
            // ProveedorSeeder::class,
            // InsumoSeeder::class,
            // ProduccionSeeder::class,
            // LogisticaSeeder::class,
            // TrazabilidadSeeder::class,
        ]);
    }
}
