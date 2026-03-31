<?php

namespace App\Policies;

use App\Models\OrdenProduccion;
use App\Models\User;

class OrdenProduccionPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->canCustom('Produccion', 'ver') ?? false;
    }

    public function view(?User $user, OrdenProduccion $ordenProduccion): bool
    {
        return $user?->canCustom('Produccion', 'ver') ?? false;
    }

    public function create(?User $user): bool
    {
        return $user?->canCustom('Produccion', 'crear') ?? false;
    }

    public function update(?User $user, OrdenProduccion $ordenProduccion): bool
    {
        if (! $user || ! $user->canCustom('Produccion', 'editar')) {
            return false;
        }

        // La regla pide responsable_id o creado_por; en este modelo el equivalente actual es user_id.
        $esResponsable = (int) ($ordenProduccion->responsable_id ?? $ordenProduccion->user_id ?? 0) === (int) $user->id;
        $esCreador = (int) ($ordenProduccion->creado_por ?? $ordenProduccion->user_id ?? 0) === (int) $user->id;

        return $esResponsable || $esCreador;
    }

    public function delete(?User $user, OrdenProduccion $ordenProduccion): bool
    {
        return ($user?->canCustom('Produccion', 'eliminar') ?? false)
            && ((int) $ordenProduccion->user_id === (int) $user->id);
    }

    public function restore(?User $user, OrdenProduccion $ordenProduccion): bool
    {
        return $user?->canCustom('Produccion', 'editar') ?? false;
    }

    public function forceDelete(?User $user, OrdenProduccion $ordenProduccion): bool
    {
        return $user?->canCustom('Produccion', 'eliminar') ?? false;
    }
}
