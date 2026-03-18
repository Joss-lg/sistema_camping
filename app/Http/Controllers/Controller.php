<?php

namespace App\Http\Controllers;

use App\Models\UsuarioPermiso;

abstract class Controller
{
    protected function canViewModule(string $modulo): bool
    {
        return $this->resolveModulePermission($modulo, false);
    }

    protected function canEditModule(string $modulo): bool
    {
        return $this->resolveModulePermission($modulo, true);
    }

    private function resolveModulePermission(string $modulo, bool $requiresEdit): bool
    {
        $usuarioId = (int) session('auth_user_id', 0);
        $rol = strtoupper((string) session('auth_user_rol', ''));

        if ($usuarioId <= 0) {
            return false;
        }

        $permisos = UsuarioPermiso::where('usuario_id', $usuarioId)->get(['modulo', 'puede_ver', 'puede_editar']);

        // Fallback para usuarios existentes sin matriz de permisos cargada.
        if ($permisos->isEmpty()) {
            return $this->resolveLegacyRolePermission($rol, $modulo, $requiresEdit);
        }

        $permiso = $permisos->firstWhere('modulo', $modulo);
        if (! $permiso) {
            return false;
        }

        if ($requiresEdit) {
            return (bool) $permiso->puede_editar;
        }

        return (bool) $permiso->puede_ver || (bool) $permiso->puede_editar;
    }

    private function resolveLegacyRolePermission(string $rol, string $modulo, bool $requiresEdit): bool
    {
        if ($rol === 'ADMIN') {
            return true;
        }

        if ($rol === 'ALMACEN') {
            $allowed = [
                'Dashboard',
                'Compras',
                'Insumos',
                'Produccion',
                'Terminados',
                'Trazabilidad',
                'Reportes',
            ];

            return in_array($modulo, $allowed, true);
        }

        if ($rol === 'PROVEEDOR' && ! $requiresEdit) {
            return $modulo === 'Compras';
        }

        return false;
    }
}
