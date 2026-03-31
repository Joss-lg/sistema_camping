<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class AssignAdminPermissions extends Command
{
    protected $signature = 'permissions:assign-admin';

    protected $description = 'Asignar todos los permisos al rol de Super Administrador';

    public function handle(): int
    {
        $adminRole = Role::whereIn('slug', ['super_admin', 'super-admin', 'admin'])
            ->orWhere('nombre', 'Super Administrador')
            ->first();

        if (!$adminRole) {
            $this->error('Rol de Super Administrador no encontrado.');
            return 1;
        }

        $modulos = [
            'Insumos',
            'Compras',
            'Produccion',
            'Proveedores',
            'Usuarios',
            'Reportes',
            'Configuracion',
        ];

        $permisos = ['puede_ver', 'puede_crear', 'puede_editar', 'puede_eliminar', 'puede_aprobar'];

        foreach ($modulos as $modulo) {
            foreach ($permisos as $permiso) {
                $existe = Permission::where('role_id', $adminRole->id)
                    ->whereRaw('LOWER(modulo) = ?', [mb_strtolower($modulo)])
                    ->first();

                if (!$existe) {
                    Permission::create([
                        'role_id' => $adminRole->id,
                        'modulo' => $modulo,
                        'puede_ver' => true,
                        'puede_crear' => true,
                        'puede_editar' => true,
                        'puede_eliminar' => true,
                        'puede_aprobar' => true,
                    ]);
                    $this->info("✅ Permisos creados para módulo: {$modulo}");
                } else {
                    // Actualizar para asegurar que todos están en true
                    $existe->update([
                        'puede_ver' => true,
                        'puede_crear' => true,
                        'puede_editar' => true,
                        'puede_eliminar' => true,
                        'puede_aprobar' => true,
                    ]);
                    $this->info("🔄 Permisos actualizados para módulo: {$modulo}");
                }
            }
        }

        $this->info("\n✅ Todos los permisos han sido asignados al Super Administrador");
        return 0;
    }
}
