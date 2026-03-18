<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\UsuarioPermiso;

class PermisoService
{
    public static function asignarPermisosPredeterminados(Usuario $usuario): void
    {
        $permisos = self::permisosPredeterminados()[$usuario->rol] ?? [];

        // Limpiar permisos existentes
        UsuarioPermiso::where('usuario_id', $usuario->id)->delete();

        if (!empty($permisos['modulos'])) {
            foreach ($permisos['modulos'] as $modulo) {
                UsuarioPermiso::create([
                    'usuario_id' => $usuario->id,
                    'modulo' => $modulo,
                    'puede_ver' => true,
                    'puede_editar' => in_array($modulo, $permisos['puede_editar'] ?? []),
                ]);
            }
        }
    }

    public static function getPermisosPredeterminados(): array
    {
        return self::permisosPredeterminados();
    }

    private static function permisosPredeterminados(): array
    {
        return [
            'ADMIN' => [
                'modulos' => ['Dashboard','Proveedores','Compras','Insumos','Produccion','Terminados','Trazabilidad','Reportes','Crear usuarios y otorgar permisos'],
                'puede_editar' => ['Dashboard','Proveedores','Compras','Insumos','Produccion','Terminados','Trazabilidad','Reportes','Crear usuarios y otorgar permisos'],
            ],
            'ALMACEN' => [
                'modulos' => ['Dashboard','Compras','Insumos','Produccion','Terminados','Trazabilidad','Reportes'],
                'puede_editar' => ['Compras','Insumos','Produccion','Terminados'],
            ],
            'PROVEEDOR' => [
                'modulos' => ['Dashboard','Proveedores'],
                'puede_editar' => ['Proveedores'],
            ],
        ];
    }
}