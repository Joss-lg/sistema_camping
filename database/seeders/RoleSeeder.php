<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Super Administrador',
                'slug' => 'super_admin',
                'descripcion' => 'Acceso total al sistema y configuraciones avanzadas',
                'nivel_acceso' => 100,
            ],
            [
                'nombre' => 'Gerente de Producción',
                'slug' => 'gerente_produccion',
                'descripcion' => 'Gestión integral de órdenes y trazabilidad de producción',
                'nivel_acceso' => 80,
            ],
            [
                'nombre' => 'Supervisor de Almacén',
                'slug' => 'supervisor_almacen',
                'descripcion' => 'Control operativo de inventario, recepciones y salidas',
                'nivel_acceso' => 60,
            ],
            [
                'nombre' => 'Operador',
                'slug' => 'operador',
                'descripcion' => 'Ejecución de tareas operativas en producción y almacén',
                'nivel_acceso' => 40,
            ],
            [
                'nombre' => 'Proveedor',
                'slug' => 'proveedor',
                'descripcion' => 'Acceso restringido únicamente a la consulta de sus entregas',
                'nivel_acceso' => 20,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
