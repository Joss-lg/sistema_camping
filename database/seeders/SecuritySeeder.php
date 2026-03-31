<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecuritySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $modules = [
            'Dashboard',
            'Proveedores',
            'Compras',
            'Insumos',
            'Produccion',
            'Terminados',
            'Trazabilidad',
            'Reportes',
            'Permisos',
        ];

        // Roles y sus permisos predeterminados
        $rolePermissions = [
            [
                'nombre' => 'Administrador',
                'slug' => 'admin',
                'nivel_acceso' => 10,
                'descripcion' => 'Control total del sistema',
                'permissions' => array_fill_keys($modules, [
                    'puede_ver' => true,
                    'puede_crear' => true,
                    'puede_editar' => true,
                    'puede_eliminar' => true,
                    'puede_aprobar' => true,
                ]),
            ],
            [
                'nombre' => 'Gerente de Producción',
                'slug' => 'gerente-produccion',
                'nivel_acceso' => 8,
                'descripcion' => 'Gestiona órdenes de producción',
                'permissions' => [
                    'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Produccion' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                    'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Insumos' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Terminados' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                    'Reportes' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                ],
            ],
            [
                'nombre' => 'Gerente de Compras',
                'slug' => 'gerente-compras',
                'nivel_acceso' => 7,
                'descripcion' => 'Gestiona compras y proveedores',
                'permissions' => [
                    'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Compras' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                    'Proveedores' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Reportes' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                ],
            ],
            [
                'nombre' => 'Supervisor de Almacén',
                'slug' => 'supervisor-almacen',
                'nivel_acceso' => 6,
                'descripcion' => 'Controla inventario y recepciones',
                'permissions' => [
                    'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Insumos' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Compras' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => true],
                    'Terminados' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                ],
            ],
            [
                'nombre' => 'Operador de Producción',
                'slug' => 'operador-produccion',
                'nivel_acceso' => 4,
                'descripcion' => 'Ejecuta operaciones de manufactura',
                'permissions' => [
                    'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Produccion' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Insumos' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                ],
            ],
            [
                'nombre' => 'Control de Calidad',
                'slug' => 'control-calidad',
                'nivel_acceso' => 5,
                'descripcion' => 'Inspecciona y valida productos',
                'permissions' => [
                    'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Terminados' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                    'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                    'Reportes' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                ],
            ],
            [
                'nombre' => 'Solo Lectura',
                'slug' => 'solo-lectura',
                'nivel_acceso' => 1,
                'descripcion' => 'Visualización únicamente',
                'permissions' => array_fill_keys($modules, [
                    'puede_ver' => true,
                    'puede_crear' => false,
                    'puede_editar' => false,
                    'puede_eliminar' => false,
                    'puede_aprobar' => false,
                ]),
            ],
        ];

        // Crear roles y permisos
        foreach ($rolePermissions as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Crear permisos para este rol
            foreach ($permissions as $modulo => $perms) {
                Permission::updateOrCreate(
                    [
                        'role_id' => $role->id,
                        'modulo' => $modulo,
                    ],
                    $perms
                );
            }
        }

        // Crear usuario administrador
        $adminRole = Role::where('slug', 'admin')->first();

        User::updateOrCreate(
            ['email' => 'admin@logicamp.local'],
            [
                'name' => 'Administrador del Sistema',
                'password' => Hash::make('admin123456'),
                'role_id' => $adminRole->id,
                'activo' => true,
                'departamento' => 'Administración',
                'telefono' => '+1-555-0000',
            ]
        );
    }
}
