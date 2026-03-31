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
            // Seeders base solicitados
            RoleSeeder::class,
            //UserSeeder::class,
            //CatalogosSeeder::class,
            //LogisticaSeeder::class,
            //ProduccionSeeder::class,

            // Phase 4: Purchasing - Insumos and Orders
            //InsumoSeeder::class,
            //OrdenCompraSeeder::class,

            // Phase 5: Production & Traceability
            //OrdenProduccionSeeder::class,
            //TrazabilidadSeeder::class,

            // Phase 6: Configuracion, Notificaciones y Reportes
            //ConfiguracionSistemaSeeder::class,
            //NotificacionSistemaSeeder::class,
            //ReporteGeneradoSeeder::class,
        ]);
    }
}
