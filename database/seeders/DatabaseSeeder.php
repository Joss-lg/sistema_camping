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
            // Phase 1: Seguridad y Roles
            RoleSeeder::class,
            SecuritySeeder::class,

            // Phase 2: Catálogos Base
            UnidadMedidaSeeder::class,
            CategoriaInsumoSeeder::class,
            TipoProductoSeeder::class,
            UbicacionAlmacenSeeder::class,

            // Phase 3: Usuarios y Proveedores
            UserSeeder::class,
            ProveedorSeeder::class,

            // Phase 4: Insumos y Órdenes de Compra
            InsumoSeeder::class,
            OrdenCompraSeeder::class,

            // Phase 5: Producción y Trazabilidad
            EtapaProduccionPlantillaSeeder::class,
            OrdenProduccionSeeder::class,
            TrazabilidadSeeder::class,

            // Phase 6: Configuración, Notificaciones y Reportes
            ConfiguracionSistemaSeeder::class,
            NotificacionSistemaSeeder::class,
            ReporteGeneradoSeeder::class,
        ]);
    }
}
