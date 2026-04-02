<?php

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrdenCompraPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->canCustom('Compras', 'ver') ?? false;
    }

    public function view(?User $user, OrdenCompra $ordenCompra): bool
    {
        if (! $user || ! $user->canCustom('Compras', 'ver')) {
            return false;
        }

        return $this->canAccessOrdenForProveedorScope($user, $ordenCompra);
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

        if (! $this->canAccessOrdenForProveedorScope($user, $ordenCompra)) {
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

        if (! $this->canAccessOrdenForProveedorScope($user, $ordenCompra)) {
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

    private function canAccessOrdenForProveedorScope(User $user, OrdenCompra $ordenCompra): bool
    {
        if (! $this->isProveedorRole($user)) {
            return true;
        }

        $proveedorIds = $this->resolveProveedorIdsForUser($user);

        if (count($proveedorIds) === 0) {
            return false;
        }

        return in_array((int) $ordenCompra->proveedor_id, $proveedorIds, true);
    }

    private function isProveedorRole(User $user): bool
    {
        $roleName = mb_strtoupper((string) ($user->role?->slug ?: $user->role?->nombre ?: ''));

        return $roleName === 'PROVEEDOR';
    }

    /**
     * @return array<int>
     */
    private function resolveProveedorIdsForUser(User $user): array
    {
        $proveedorId = (int) ($user->proveedor_id ?? 0);

        if ($proveedorId > 0) {
            return [$proveedorId];
        }

        $email = $user->email;

        if (! $email) {
            return [];
        }

        return DB::table('proveedores')
            ->select('proveedores.id')
            ->leftJoin('contactos_proveedores', 'contactos_proveedores.proveedor_id', '=', 'proveedores.id')
            ->where(function ($query) use ($email): void {
                $query->where('proveedores.email_general', $email)
                    ->orWhere('contactos_proveedores.email', $email);
            })
            ->distinct()
            ->pluck('proveedores.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
