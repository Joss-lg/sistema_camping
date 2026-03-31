<?php

namespace App\Policies;

use App\Models\Insumo;
use App\Models\User;

class InsumoPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->canCustom('Insumos', 'ver') ?? false;
    }

    public function view(?User $user, Insumo $insumo): bool
    {
        return $user?->canCustom('Insumos', 'ver') ?? false;
    }

    public function create(?User $user): bool
    {
        return $user?->canCustom('Insumos', 'crear') ?? false;
    }

    public function update(?User $user, Insumo $insumo): bool
    {
        return $user?->canCustom('Insumos', 'editar') ?? false;
    }

    public function delete(?User $user, Insumo $insumo): bool
    {
        return $user?->canCustom('Insumos', 'eliminar') ?? false;
    }

    public function restore(?User $user, Insumo $insumo): bool
    {
        return $user?->canCustom('Insumos', 'editar') ?? false;
    }

    public function forceDelete(?User $user, Insumo $insumo): bool
    {
        return $user?->canCustom('Insumos', 'eliminar') ?? false;
    }
}
