<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\Usuario;
use App\Models\UsuarioPermiso;
use App\Services\PermisoService;

class ProveedorController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->canViewProveedores()) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver proveedores.');
        }

        $query = trim((string) $request->query('q', ''));

        $proveedores = Proveedor::with('estado')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('nombre', 'like', "%{$query}%")
                        ->orWhere('contacto', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('telefono', 'like', "%{$query}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('proveedores.index', [
            'proveedores' => $proveedores,
            'q' => $query,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->canEditProveedores()) {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para crear proveedores.');
        }

        return view('proveedores.create', [
            'estados' => $this->estadosProveedor(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canEditProveedores()) {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para crear proveedores.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150', 'unique:proveedor,email'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado_id' => ['required', 'integer', 'exists:estado,id'],
        ]);

        Proveedor::create($data);
            // Crear usuario vinculado al proveedor
            if (!empty($data['email'])) {
                $usuario = Usuario::create([
                    'nombre' => $data['nombre'],
                    'email' => $data['email'],
                    'rol' => 'PROVEEDOR',
                    'password' => bcrypt('contraseña_inicial'), // Puedes cambiar la contraseña por defecto
                    'estado_id' => $data['estado_id'],
                ]);

                // Asignar permisos predeterminados para el rol PROVEEDOR
                PermisoService::asignarPermisosPredeterminados($usuario);
            }

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor): View|RedirectResponse
    {
        if (! $this->canEditProveedores()) {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para editar proveedores.');
        }

        return view('proveedores.edit', [
            'proveedor' => $proveedor,
            'estados' => $this->estadosProveedor(),
        ]);
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        if (! $this->canEditProveedores()) {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para editar proveedores.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:120'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('proveedor', 'email')->ignore($proveedor->id),
            ],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado_id' => ['required', 'integer', 'exists:estado,id'],
        ]);

        $proveedor->update($data);

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor actualizado correctamente.');
    }

    public function toggleEstado(Proveedor $proveedor): RedirectResponse
    {
        if (! $this->canEditProveedores()) {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para editar proveedores.');
        }

        $activo = Estado::firstOrCreate(['nombre' => 'Activo', 'tipo' => 'general']);
        $inactivo = Estado::firstOrCreate(['nombre' => 'Inactivo', 'tipo' => 'general']);

        $nuevoEstadoId = ((int) $proveedor->estado_id === (int) $activo->id) ? $inactivo->id : $activo->id;

        $proveedor->update(['estado_id' => $nuevoEstadoId]);

        // Sincronizar estado con el usuario correspondiente
        $usuario = Usuario::where('email', $proveedor->email)->where('rol', 'PROVEEDOR')->first();
        if ($usuario) {
            $usuario->update(['estado_id' => $nuevoEstadoId]);
        }

        return redirect()->route('proveedores.index')->with('ok', 'Estado de proveedor actualizado.');
    }

    private function estadosProveedor()
    {
        Estado::firstOrCreate(['nombre' => 'Activo', 'tipo' => 'general']);
        Estado::firstOrCreate(['nombre' => 'Inactivo', 'tipo' => 'general']);

        return Estado::where('tipo', 'general')
            ->whereIn('nombre', ['Activo', 'Inactivo'])
            ->orderBy('nombre')
            ->get();
    }

    private function canViewProveedores(): bool
    {
        return $this->canViewModule('Proveedores');
    }

    private function canEditProveedores(): bool
    {
        return $this->canEditModule('Proveedores');
    }
}
