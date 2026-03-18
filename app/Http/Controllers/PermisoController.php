<?php
namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\PermisoService;
use App\Models\Proveedor;

class PermisoController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! $this->canViewModule('Crear usuarios y otorgar permisos')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver este modulo.');
        }

        $rolFiltro = request('rol');

        $usuarios = Usuario::with('permisos', 'estado')
            ->when($rolFiltro, function($query) use ($rolFiltro) {
                $query->where('rol', $rolFiltro);
            })
            ->orderByDesc('id')
            ->get();

        $registros = $usuarios->map(function($usuario) {
            return [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado->nombre ?? 'Desconocido',
                'permisos' => $usuario->permisos->map(function($p) {
                    return ['modulo' => $p->modulo, 'puede_editar' => $p->puede_editar];
                })
            ];
        });

        return view('permisos.index', [
            'modulos' => $this->modulosDisponibles(),
            'registros' => $registros,
            'permisosPredeterminados' => PermisoService::getPermisosPredeterminados(),
            'rolFiltro' => $rolFiltro,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canEditModule('Crear usuarios y otorgar permisos')) {
            return redirect()->route('permisos.index')->with('error', 'No tienes permisos para crear usuarios.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:usuario,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'rol' => ['required', 'in:ADMIN,ALMACEN,PROVEEDOR'],
            'modulos' => ['sometimes', 'array'],
            'puede_editar' => ['sometimes', 'array'],
        ]);

        $estado = Estado::firstOrCreate(['nombre' => 'Activo', 'tipo' => 'general']);

        DB::transaction(function () use ($data, $estado) {
            $usuario = Usuario::create([
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'rol' => $data['rol'],
                'estado_id' => $estado->id,
            ]);

            // Asignar permisos predeterminados basados en el rol
            PermisoService::asignarPermisosPredeterminados($usuario);

            // Luego, aplicar los permisos seleccionados manualmente
            if (!empty($data['modulos'])) {
                foreach ($data['modulos'] as $modulo) {
                    UsuarioPermiso::updateOrCreate(
                        ['usuario_id' => $usuario->id, 'modulo' => $modulo],
                        [
                            'puede_ver' => true,
                            'puede_editar' => in_array($modulo, $data['puede_editar'] ?? []),
                        ]
                    );
                }
            }
        });

        return redirect()->route('permisos.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // CORRECCIÓN: Buscamos manualmente para evitar el 404 del Route Model Binding
        $usuario = Usuario::findOrFail($id);

        if (! $this->canEditModule('Crear usuarios y otorgar permisos')) {
            return redirect()->route('permisos.index')->with('error', 'No tienes permisos.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuario', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'rol' => ['required', 'in:ADMIN,ALMACEN,PROVEEDOR'],
            'modulos' => ['sometimes', 'array'],
            'puede_editar' => ['sometimes', 'array'],
        ]);

        DB::transaction(function () use ($data, $usuario) {
            $payload = [
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'rol' => $data['rol'],
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $usuario->update($payload);

            // Sincronizar permisos
            UsuarioPermiso::where('usuario_id', $usuario->id)->delete();
            if (!empty($data['modulos'])) {
                foreach ($data['modulos'] as $modulo) {
                    UsuarioPermiso::create([
                        'usuario_id' => $usuario->id,
                        'modulo' => $modulo,
                        'puede_ver' => true,
                        'puede_editar' => in_array($modulo, $data['puede_editar'] ?? []),
                    ]);
                }
            }
        });

        return redirect()->route('permisos.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        if (! $this->canEditModule('Crear usuarios y otorgar permisos')) {
            $message = 'No tienes permisos para eliminar usuarios.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return redirect()->route('permisos.index')->with('error', $message);
        }

        try {
            $usuario = Usuario::findOrFail($id);

            DB::transaction(function () use ($usuario) {
                // Eliminar permisos asociados
                UsuarioPermiso::where('usuario_id', $usuario->id)->delete();
                // Eliminar el usuario
                $usuario->delete();
            });

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
            }

            return redirect()->route('permisos.index')->with('ok', 'Usuario eliminado correctamente.');
        } catch (\Exception $e) {
            $message = 'Error al eliminar el usuario.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message, 'details' => $e->getMessage()], 500);
            }
            return redirect()->route('permisos.index')->with('error', $message);
        }
    }

    private function modulosDisponibles(): array {
        return ['Dashboard','Proveedores','Compras','Insumos','Produccion','Terminados','Trazabilidad','Reportes','Crear usuarios y otorgar permisos'];
    }

    public function toggleEstado(Request $request, $id): RedirectResponse
    {
        if (! $this->canEditModule('Crear usuarios y otorgar permisos')) {
            return redirect()->route('permisos.index')->with('error', 'No tienes permisos.');
        }

        $usuario = Usuario::findOrFail($id);

        $activo = Estado::firstOrCreate(['nombre' => 'Activo', 'tipo' => 'general']);
        $inactivo = Estado::firstOrCreate(['nombre' => 'Inactivo', 'tipo' => 'general']);

        $nuevoEstadoId = ($usuario->estado_id == $activo->id) ? $inactivo->id : $activo->id;

        $usuario->update(['estado_id' => $nuevoEstadoId]);

        // Si es PROVEEDOR, también cambiar el estado del proveedor
        if ($usuario->rol == 'PROVEEDOR') {
            $proveedor = Proveedor::where('email', $usuario->email)->first();
            if ($proveedor) {
                $proveedor->update(['estado_id' => $nuevoEstadoId]);
            }
        }

        return redirect()->back()->with('ok', 'Estado del usuario actualizado correctamente.');
    }
}