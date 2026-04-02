<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecuritySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $modules = [
            'Dashboard', 'Proveedores', 'Compras', 'Insumos', 'Produccion',
            'Terminados', 'Trazabilidad', 'Reportes', 'Permisos', 'Entregas',
        ];

        $rolesMapped = [
            'super_admin' => Role::where('slug', 'super_admin')->first(),
            'gerente_produccion' => Role::where('slug', 'gerente_produccion')->first(),
            'supervisor_almacen' => Role::where('slug', 'supervisor_almacen')->first(),
            'operador' => Role::where('slug', 'operador')->first(),
            'proveedor' => Role::where('slug', 'proveedor')->first(),
        ];

        $rolePermissions = [
            'super_admin' => array_fill_keys($modules, [
                'puede_ver' => true, 'puede_crear' => true, 'puede_editar' => true,
                'puede_eliminar' => true, 'puede_aprobar' => true,
            ]),
            'gerente_produccion' => [
                'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Produccion' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Insumos' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Terminados' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => true],
                'Reportes' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
            ],
            'supervisor_almacen' => [
                'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Insumos' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Compras' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => true],
                'Terminados' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Entregas' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
            ],
            'operador' => [
                'Dashboard' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Produccion' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => true, 'puede_eliminar' => false, 'puede_aprobar' => false],
                'Trazabilidad' => ['puede_ver' => true, 'puede_crear' => true, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
            ],
            'proveedor' => [
                'Entregas' => ['puede_ver' => true, 'puede_crear' => false, 'puede_editar' => false, 'puede_eliminar' => false, 'puede_aprobar' => false],
            ],
        ];

        foreach ($rolePermissions as $roleSlug => $permissions) {
            $role = $rolesMapped[$roleSlug] ?? null;
            if (!$role) continue;

            foreach ($permissions as $modulo => $perms) {
                Permission::updateOrCreate(
                    ['role_id' => $role->id, 'modulo' => $modulo],
                    $perms
                );
            }
        }
    }
}
