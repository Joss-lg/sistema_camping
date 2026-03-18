<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $rol = strtoupper((string) $request->session()->get('auth_user_rol', ''));

        if ($rol !== 'ADMIN') {
            return redirect()->route('dashboard')->with('error', 'Solo los administradores pueden acceder a ese modulo.');
        }

        return $next($request);
    }
}
