<?php

namespace App\Services;

use App\Models\User;

class AbastecimientoResponsableResolver
{
    /**
     * Resuelve el usuario responsable para órdenes automáticas.
     */
    public function resolve(): ?int
    {
        $usuario = User::query()
            ->where('activo', true)
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['super_admin', 'super-admin', 'supervisor_almacen', 'gerente_produccion']);
            })
            ->orderBy('id')
            ->first();

        if ($usuario) {
            return (int) $usuario->id;
        }

        return User::query()
            ->where('activo', true)
            ->orderBy('id')
            ->value('id');
    }
}
