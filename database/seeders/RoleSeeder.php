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
                'nombre' => 'Administrador',
                'slug' => 'administrador',
                'descripcion' => 'Acceso total al sistema LogiCamp y gestión de usuarios',
                'nivel_acceso' => 100,
            ],
            [
                'nombre' => 'Encargado',
                'slug' => 'encargado',
                'descripcion' => 'Supervisión de operaciones diarias: almacén, compras y producción',
                'nivel_acceso' => 70,
            ],
            [
                'nombre' => 'Trabajador',
                'slug' => 'trabajador',
                'descripcion' => 'Ejecución de tareas operativas en planta y producción',
                'nivel_acceso' => 40,
            ],
            [
                'nombre' => 'Proveedor',
                'slug' => 'proveedor',
                'descripcion' => 'Acceso restringido a la consulta de sus entregas',
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
