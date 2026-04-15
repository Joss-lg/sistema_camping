<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermisoService
{
    /**
     * @return array<string, array{nombre: string, slug: string, descripcion: string, nivel_acceso: int}>
     */
    public static function coreRolesCatalog(): array
    {
        return [
            'ADMINISTRADOR' => [
                'nombre' => 'Administrador',
                'slug' => 'administrador',
                'descripcion' => 'Acceso total al sistema LogiCamp y gestión de usuarios',
                'nivel_acceso' => 100,
            ],
            'ENCARGADO' => [
                'nombre' => 'Encargado',
                'slug' => 'encargado',
                'descripcion' => 'Supervisión de operaciones diarias: almacén, compras y producción',
                'nivel_acceso' => 70,
            ],
            'TRABAJADOR' => [
                'nombre' => 'Trabajador',
                'slug' => 'trabajador',
                'descripcion' => 'Ejecución de tareas operativas en planta y producción',
                'nivel_acceso' => 40,
            ],
            'PROVEEDOR' => [
                'nombre' => 'Proveedor',
                'slug' => 'proveedor',
                'descripcion' => 'Acceso restringido a la consulta de sus entregas',
                'nivel_acceso' => 20,
            ],
        ];
    }

    /**
     * Sincroniza los 4 roles oficiales del sistema sin depender de seeders.
     * También migra usuarios con roles legacy a los nuevos roles.
     */
    public static function ensureCoreRoles(): void
    {
        DB::transaction(function (): void {
            $catalog = self::coreRolesCatalog();
            $byKey = [];

            foreach ($catalog as $roleKey => $payload) {
                $role = Role::query()->updateOrCreate(
                    ['slug' => $payload['slug']],
                    $payload
                );

                $byKey[$roleKey] = $role;

                $roleYaTienePermisos = Permission::query()
                    ->where('role_id', $role->id)
                    ->exists();

                if (! $roleYaTienePermisos) {
                    self::syncRolePermissions($role);
                }
            }

            // Migración de slugs legacy al nuevo catálogo.
            $legacyToNew = [
                'super_admin' => 'ADMINISTRADOR',
                'gerente_produccion' => 'ENCARGADO',
                'supervisor_almacen' => 'ENCARGADO',
                'operador' => 'TRABAJADOR',
                'admin' => 'ADMINISTRADOR',
                'almacen' => 'ENCARGADO',
            ];

            foreach ($legacyToNew as $legacySlug => $targetKey) {
                $legacyRole = Role::query()->where('slug', $legacySlug)->first();

                if (! $legacyRole || ! isset($byKey[$targetKey])) {
                    continue;
                }

                User::query()
                    ->where('role_id', $legacyRole->id)
                    ->update(['role_id' => $byKey[$targetKey]->id]);

                Permission::query()->where('role_id', $legacyRole->id)->delete();
                $legacyRole->delete();
            }

            // Limpieza defensiva: elimina roles fuera del catálogo oficial.
            $allowedSlugs = array_map(
                fn (array $role): string => (string) $role['slug'],
                array_values($catalog)
            );

            $rolesFueraCatalogo = Role::query()
                ->whereNotIn('slug', $allowedSlugs)
                ->get(['id']);

            foreach ($rolesFueraCatalogo as $roleExtra) {
                $usuariosConRol = User::query()->where('role_id', $roleExtra->id)->exists();

                if ($usuariosConRol) {
                    continue;
                }

                Permission::query()->where('role_id', $roleExtra->id)->delete();
                Role::query()->whereKey($roleExtra->id)->delete();
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public static function modulosDisponibles(): array
    {
        return [
            'Dashboard',
            'Insumos',
            'Compras',
            'Produccion',
            'Entregas',
            'Terminados',
            'Trazabilidad',
            'Proveedores',
            'Reportes',
            'Permisos',
        ];
    }

    /**
     * @return array<string, array{modulos: array<int, string>, puede_editar: array<int, string>}>
     */
    public static function getPermisosPredeterminados(): array
    {
        $all = self::modulosDisponibles();

        return [
            'ADMINISTRADOR' => [
                'modulos' => $all,
                'puede_editar' => $all,
            ],
            'ENCARGADO' => [
                'modulos' => ['Dashboard', 'Insumos', 'Compras', 'Produccion', 'Entregas', 'Terminados', 'Trazabilidad', 'Proveedores', 'Reportes'],
                'puede_editar' => ['Insumos', 'Compras', 'Produccion', 'Entregas', 'Terminados'],
            ],
            'TRABAJADOR' => [
                'modulos' => ['Dashboard', 'Insumos', 'Compras', 'Produccion', 'Entregas', 'Terminados', 'Trazabilidad'],
                'puede_editar' => ['Produccion'],
            ],
            'PROVEEDOR' => [
                'modulos' => ['Entregas'],
                'puede_editar' => [],
            ],
        ];
    }

    public static function normalizeRoleKey(string|null $role): string
    {
        $normalized = mb_strtolower(trim((string) $role));
        $normalized = str_replace(['-', ' '], '_', $normalized);
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $normalized);

        return match ($normalized) {
            'administrador', 'admin', 'super_admin', 'super_administrador' => 'ADMINISTRADOR',
            'encargado' => 'ENCARGADO',
            'trabajador', 'operador' => 'TRABAJADOR',
            'proveedor' => 'PROVEEDOR',
            default => strtoupper($normalized),
        };
    }

    public static function isSuperAdmin(User|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $roleKey = self::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre));

        return $roleKey === 'ADMINISTRADOR';
    }

    /**
     * @return array{modulos: array<int, string>, puede_editar: array<int, string>}
     */
    public static function resolvePermisosPredeterminados(string|null $role): array
    {
        $defaults = self::getPermisosPredeterminados();

        return $defaults[self::normalizeRoleKey($role)] ?? ['modulos' => [], 'puede_editar' => []];
    }

    public static function resolveRoleByInput(string $roleInput): ?Role
    {
        $roleKey = self::normalizeRoleKey($roleInput);

        return Role::query()
            ->get(['id', 'slug', 'nombre'])
            ->first(function (Role $role) use ($roleKey, $roleInput): bool {
                return self::normalizeRoleKey($role->slug) === $roleKey
                    || self::normalizeRoleKey($role->nombre) === $roleKey
                    || strtoupper((string) $role->slug) === strtoupper($roleInput)
                    || strtoupper((string) $role->nombre) === strtoupper($roleInput);
            });
    }

    /**
     * @param array<int, string>|null $modulos
     * @param array<int, string>|null $editables
     */
    public static function syncRolePermissions(Role $role, ?array $modulos = null, ?array $editables = null): void
    {
        $defaults = self::resolvePermisosPredeterminados($role->slug ?: $role->nombre);

        $modulosFinales = collect($modulos ?? $defaults['modulos'])
            ->map(fn ($modulo): string => trim((string) $modulo))
            ->filter()
            ->unique()
            ->values();

        $editablesFinales = collect($editables ?? $defaults['puede_editar'])
            ->map(fn ($modulo): string => trim((string) $modulo))
            ->filter()
            ->unique()
            ->values()
            ->all();

        Permission::query()
            ->where('role_id', $role->id)
            ->whereNotIn('modulo', $modulosFinales->all())
            ->delete();

        foreach ($modulosFinales as $modulo) {
            $canEdit = in_array($modulo, $editablesFinales, true);

            Permission::query()->updateOrCreate(
                ['role_id' => $role->id, 'modulo' => $modulo],
                [
                    'puede_ver' => true,
                    'puede_crear' => $canEdit,
                    'puede_editar' => $canEdit,
                    'puede_eliminar' => $canEdit,
                    'puede_aprobar' => $canEdit,
                ]
            );
        }
    }

    public static function canAccessModule(User|null $user, string $module, string $action = 'ver'): bool
    {
        if (! $user) {
            return false;
        }

        return self::isSuperAdmin($user) || $user->canCustom($module, $action);
    }

    public static function resolveLandingRoute(User|null $user): string
    {
        if (! $user) {
            return 'login';
        }

        if (self::isSuperAdmin($user) || $user->canCustom('Dashboard', 'ver')) {
            return 'dashboard';
        }

        return match (true) {
            $user->canCustom('Entregas', 'ver') => 'entregas.index',
            $user->canCustom('Produccion', 'ver') => 'produccion.index',
            $user->canCustom('Insumos', 'ver') => 'insumos.index',
            $user->canCustom('Compras', 'ver') => 'ordenes-compra.index',
            $user->canCustom('Trazabilidad', 'ver') => 'trazabilidad.index',
            $user->canCustom('Terminados', 'ver') => 'terminados.index',
            $user->canCustom('Reportes', 'ver') => 'reportes.index',
            $user->canCustom('Proveedores', 'ver') => 'proveedores.index',
            default => 'dashboard',
        };
    }
}