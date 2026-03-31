<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\PermisoService;
use Illuminate\Console\Command;

class SyncDefaultPermissions extends Command
{
    protected $signature = 'permissions:sync-defaults {--role= : Slug o nombre del rol a sincronizar}';

    protected $description = 'Sincroniza los permisos predeterminados definidos por rol';

    public function handle(): int
    {
        $roleOption = trim((string) $this->option('role'));

        if ($roleOption !== '') {
            $role = PermisoService::resolveRoleByInput($roleOption);

            if (! $role) {
                $this->error('Rol no encontrado para sincronizar.');

                return self::FAILURE;
            }

            PermisoService::syncRolePermissions($role);

            $this->info(sprintf(
                'Permisos sincronizados para %s (%s).',
                $role->nombre,
                $role->slug
            ));

            return self::SUCCESS;
        }

        $roles = Role::query()->orderBy('id')->get();

        if ($roles->isEmpty()) {
            $this->warn('No hay roles registrados para sincronizar.');

            return self::SUCCESS;
        }

        foreach ($roles as $role) {
            PermisoService::syncRolePermissions($role);

            $this->line(sprintf(
                '- %s (%s) sincronizado',
                $role->nombre,
                $role->slug
            ));
        }

        $this->info(sprintf('Sincronizacion completada. Roles procesados: %d', $roles->count()));

        return self::SUCCESS;
    }
}