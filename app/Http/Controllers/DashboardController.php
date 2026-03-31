<?php

namespace App\Http\Controllers;

use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! PermisoService::canAccessModule(Auth::user(), 'Dashboard')) {
            $route = PermisoService::resolveLandingRoute(Auth::user());

            abort_if($route === 'dashboard', 403);

            return redirect()->route($route);
        }

        return view('dashboard.simple');
    }
}
