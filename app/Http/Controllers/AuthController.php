<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('auth_user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', $credentials['email'])->first();

        if (! $usuario) {
            return back()->withErrors([
                'email' => 'Credenciales invalidas.',
            ])->onlyInput('email');
        }

        $passwordMatches = Hash::check($credentials['password'], (string) $usuario->password)
            || hash_equals((string) $usuario->password, $credentials['password']);

        if (! $passwordMatches) {
            return back()->withErrors([
                'email' => 'Credenciales invalidas.',
            ])->onlyInput('email');
        }

        $request->session()->put('auth_user_id', $usuario->id);
        $request->session()->put('auth_user_nombre', $usuario->nombre);
        $request->session()->put('auth_user_rol', $usuario->rol);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'auth_user_id',
            'auth_user_nombre',
            'auth_user_rol',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
