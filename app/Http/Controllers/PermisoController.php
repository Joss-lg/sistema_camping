<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermisoController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Permisos'), 403);

        $rolFiltro = strtoupper((string) $request->query('rol', '')) ?: null;

        $modulos = Permission::query()
            ->select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo')
            ->values()
            ->all();

        $modulos = collect(array_merge(PermisoService::modulosDisponibles(), $modulos))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $permisosPredeterminados = PermisoService::getPermisosPredeterminados();
        $rolesDisponibles = Role::query()
            ->orderBy('id')
            ->get(['nombre', 'slug'])
            ->map(fn (Role $role): string => PermisoService::normalizeRoleKey((string) ($role->slug ?: $role->nombre)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($rolesDisponibles)) {
            $rolesDisponibles = array_keys($permisosPredeterminados);
        }

        $usuarios = User::query()
            ->with(['role:id,nombre,slug'])
            ->orderBy('name')
            ->get();

        $registrosUsuarios = $usuarios
            ->map(function (User $user): array {
                $rol = PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: ($user->role?->nombre ?? 'SIN_ROL')));

                $permisos = Permission::query()
                    ->where('role_id', $user->role_id)
                    ->orderBy('modulo')
                    ->get(['modulo', 'puede_editar'])
                    ->map(fn (Permission $perm): array => [
                        'modulo' => $perm->modulo,
                        'puede_editar' => (bool) $perm->puede_editar,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'rol' => $rol,
                    'estado' => $user->activo ? 'Activo' : 'Inactivo',
                    'permisos' => $permisos,
                ];
            });
        $registros = $registrosUsuarios
            ->sortBy([
                ['rol', 'asc'],
                ['nombre', 'asc'],
            ])
            ->when($rolFiltro, fn ($collection) => $collection->where('rol', $rolFiltro))
            ->values()
            ->all();

        return view('permisos.index', compact('modulos', 'registros', 'rolFiltro', 'permisosPredeterminados', 'rolesDisponibles'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Permisos', 'editar'), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'rol' => ['required', 'string', 'max:100'],
            'modulos' => ['nullable', 'array'],
            'modulos.*' => ['string', 'max:100'],
            'puede_editar' => ['nullable', 'array'],
            'puede_editar.*' => ['string', 'max:100'],
        ]);

        $role = PermisoService::resolveRoleByInput((string) $data['rol']);

        if (! $role) {
            $role = Role::query()->orderBy('id')->first();
        }

        if (! $role) {
            return back()->withErrors(['rol' => 'No hay roles disponibles para asignar.'])->withInput();
        }

        User::query()->create([
            'name' => $data['nombre'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $role->id,
            'activo' => true,
        ]);

        PermisoService::syncRolePermissions($role, $data['modulos'] ?? null, $data['puede_editar'] ?? null);

        return redirect()->route('permisos.index')->with('ok', 'Usuario creado correctamente desde el módulo de permisos.');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule(request()->user(), 'Permisos', 'editar'), 403);

        $user = User::query()->findOrFail($id);
        $user->activo = ! $user->activo;
        $user->save();

        return back()->with('ok', 'Estado de usuario actualizado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Permisos', 'editar'), 403);

        $user = User::query()->findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'rol' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'modulos' => ['nullable', 'array'],
            'modulos.*' => ['string', 'max:100'],
            'puede_editar' => ['nullable', 'array'],
            'puede_editar.*' => ['string', 'max:100'],
        ]);

        $role = PermisoService::resolveRoleByInput((string) $data['rol']);

        if (! $role) {
            return back()->withErrors(['rol' => 'Rol no encontrado.'])->withInput();
        }

        DB::transaction(function () use ($user, $data, $role): void {
            $payload = [
                'name' => $data['nombre'],
                'email' => $data['email'],
                'role_id' => $role->id,
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            PermisoService::syncRolePermissions($role, $data['modulos'] ?? null, $data['puede_editar'] ?? null);
        });

        return redirect()->route('permisos.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Permisos', 'editar'), 403);

        $user = User::query()->findOrFail($id);

        if ((int) $user->id === (int) (Auth::id() ?? 0)) {
            $message = 'No puedes eliminar tu propia cuenta.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['usuario' => $message]);
        }

        $user->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('permisos.index')->with('ok', 'Usuario eliminado correctamente.');
    }
}
