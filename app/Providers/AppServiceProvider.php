<?php

namespace App\Providers;

use App\Models\OrdenProduccion;
use App\Models\Insumo;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\TrazabilidadEtapa;
use App\Policies\InsumoPolicy;
use App\Policies\OrdenCompraPolicy;
use App\Policies\OrdenProduccionPolicy;
use App\Policies\ProveedorPolicy;
use App\Policies\TrazabilidadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {
            $roleSlug = mb_strtolower((string) ($user?->role?->slug ?? ''));
            $roleName = mb_strtolower((string) ($user?->role?->nombre ?? ''));

            $isSuperAdmin = in_array($roleSlug, ['admin', 'super_admin', 'super-admin'], true)
                || str_contains($roleName, 'super admin')
                || str_contains($roleName, 'super administrador');

            return $isSuperAdmin ? true : null;
        });

        Gate::policy(OrdenProduccion::class, OrdenProduccionPolicy::class);
        Gate::policy(Insumo::class, InsumoPolicy::class);
        Gate::policy(OrdenCompra::class, OrdenCompraPolicy::class);
        Gate::policy(Proveedor::class, ProveedorPolicy::class);
        Gate::policy(TrazabilidadEtapa::class, TrazabilidadPolicy::class);
    }
}
