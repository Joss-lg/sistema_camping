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
use App\Services\PermisoService;
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
            return PermisoService::isSuperAdmin($user) ? true : null;
        });

        Gate::policy(OrdenProduccion::class, OrdenProduccionPolicy::class);
        Gate::policy(Insumo::class, InsumoPolicy::class);
        Gate::policy(OrdenCompra::class, OrdenCompraPolicy::class);
        Gate::policy(Proveedor::class, ProveedorPolicy::class);
        Gate::policy(TrazabilidadEtapa::class, TrazabilidadPolicy::class);
    }
}
