<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class PermisoService
{
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
            'SUPER_ADMIN' => [
                'modulos' => $all,
                'puede_editar' => $all,
            ],
            'GERENTE_PRODUCCION' => [
                'modulos' => ['Dashboard', 'Produccion', 'Terminados', 'Trazabilidad', 'Reportes', 'Entregas'],
                'puede_editar' => ['Produccion', 'Terminados', 'Trazabilidad', 'Entregas'],
            ],
            'SUPERVISOR_ALMACEN' => [
                'modulos' => ['Dashboard', 'Insumos', 'Compras', 'Entregas', 'Terminados', 'Trazabilidad', 'Reportes', 'Proveedores'],
                'puede_editar' => ['Insumos', 'Compras', 'Entregas', 'Terminados', 'Proveedores'],
            ],
            'OPERADOR' => [
                'modulos' => ['Dashboard', 'Produccion', 'Trazabilidad', 'Terminados'],
                'puede_editar' => ['Produccion'],
            ],
            'PROVEEDOR' => [
                'modulos' => ['Entregas'],
                'puede_editar' => [],
            ],
            'ADMIN' => [
                'modulos' => $all,
                'puede_editar' => $all,
            ],
            'ALMACEN' => [
                'modulos' => ['Dashboard', 'Insumos', 'Compras', 'Entregas', 'Terminados', 'Trazabilidad', 'Reportes', 'Proveedores'],
                'puede_editar' => ['Insumos', 'Compras', 'Entregas', 'Terminados', 'Proveedores'],
            ],
        ];
    }

    public static function normalizeRoleKey(string|null $role): string
    {
        $normalized = mb_strtolower(trim((string) $role));
        $normalized = str_replace(['-', ' '], '_', $normalized);
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $normalized);

        return match ($normalized) {
            'super_admin', 'super_administrador', 'administrador', 'admin' => 'SUPER_ADMIN',
            'gerente_produccion', 'gerente_de_produccion' => 'GERENTE_PRODUCCION',
            'supervisor_almacen', 'supervisor_de_almacen', 'almacen' => 'SUPERVISOR_ALMACEN',
            'operador' => 'OPERADOR',
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

        return $roleKey === 'SUPER_ADMIN';
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