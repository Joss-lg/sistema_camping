<?php

namespace App\Http\Controllers;

use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('ok', 'Registro publico deshabilitado. Solicita alta al administrador.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route(PermisoService::resolveLandingRoute(Auth::user()));
        }

        return back()->withInput()->withErrors([
            'email' => 'Credenciales invalidas.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('bienvenida');
    }
}
