<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('role')
            ->orderBy('name', 'asc');

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $usuarios = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('nombre')->get();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('nombre')->get();
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'departamento' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['activo'] = true;

        User::create($validated);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $usuario): View
    {
        $roles = Role::orderBy('nombre')->get();
        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:users,email,{$usuario->id}"],
            'role_id' => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            $validated['password'] = $request->password;
        }

        $usuario->update($validated);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        $usuarioAutenticado = Auth::user();
        if ($usuarioAutenticado && $usuarioAutenticado->id === $usuario->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    public function toggleActivo(User $usuario): RedirectResponse
    {
        $usuario->update(['activo' => !$usuario->activo]);

        $estado = $usuario->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Usuario {$estado} exitosamente.");
    }
}
