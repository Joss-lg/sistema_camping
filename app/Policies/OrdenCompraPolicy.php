<?php

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\User;

class OrdenCompraPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->canCustom('Compras', 'ver') ?? false;
    }

    public function view(?User $user, OrdenCompra $ordenCompra): bool
    {
        return $user?->canCustom('Compras', 'ver') ?? false;
    }

    public function create(?User $user): bool
    {
        return $user?->canCustom('Compras', 'crear') ?? false;
    }

    public function update(?User $user, OrdenCompra $ordenCompra): bool
    {
        if (! $user || ! $user->canCustom('Compras', 'editar')) {
            return false;
        }

        if (! $ordenCompra->puedeModificarse()) {
            return false;
        }

        return (int) $ordenCompra->user_id === (int) $user->id;
    }

    public function delete(?User $user, OrdenCompra $ordenCompra): bool
    {
        if (! $user || ! $user->canCustom('Compras', 'eliminar')) {
            return false;
        }

        return (int) $ordenCompra->user_id === (int) $user->id;
    }

    public function restore(?User $user, OrdenCompra $ordenCompra): bool
    {
        return $user?->canCustom('Compras', 'editar') ?? false;
    }

    public function forceDelete(?User $user, OrdenCompra $ordenCompra): bool
    {
        return $user?->canCustom('Compras', 'eliminar') ?? false;
    }
}
