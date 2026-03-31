<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

class ProveedorPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user?->canCustom('Proveedores', 'ver') ?? false;
    }

    public function view(?User $user, Proveedor $proveedor): bool
    {
        return $user?->canCustom('Proveedores', 'ver') ?? false;
    }

    public function create(?User $user): bool
    {
        return $user?->canCustom('Proveedores', 'crear') ?? false;
    }

    public function update(?User $user, Proveedor $proveedor): bool
    {
        return $user?->canCustom('Proveedores', 'editar') ?? false;
    }

    public function delete(?User $user, Proveedor $proveedor): bool
    {
        return $user?->canCustom('Proveedores', 'eliminar') ?? false;
    }

    public function restore(?User $user, Proveedor $proveedor): bool
    {
        return $user?->canCustom('Proveedores', 'editar') ?? false;
    }

    public function forceDelete(?User $user, Proveedor $proveedor): bool
    {
        return $user?->canCustom('Proveedores', 'eliminar') ?? false;
    }
}
