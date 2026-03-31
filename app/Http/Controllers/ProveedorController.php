<?php

namespace App\Http\Controllers;

use App\Models\ContactoProveedor;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $q = (string) $request->query('q', '');

        $query = Proveedor::query()
            ->with(['contactos' => function ($q): void {
                $q->orderByDesc('es_contacto_principal')->orderBy('id');
            }])
            ->orderBy('razon_social');

        if ($q !== '') {
            $query->where(function ($sub) use ($q): void {
                $sub->where('razon_social', 'like', '%' . $q . '%')
                    ->orWhere('nombre_comercial', 'like', '%' . $q . '%')
                    ->orWhere('email_general', 'like', '%' . $q . '%')
                    ->orWhere('telefono_principal', 'like', '%' . $q . '%');
            });
        }

        $proveedores = $query->paginate(15)->withQueryString();

        $proveedores->setCollection(
            $proveedores->getCollection()->map(fn (Proveedor $proveedor): object => $this->mapProveedorForView($proveedor))
        );

        return view('proveedores.index', compact('proveedores', 'q'));
    }

    public function create(): View
    {
        $estados = $this->estadosRelacion();

        return view('proveedores.create', compact('estados'));
    }

    public function edit(int $id): View
    {
        $proveedor = Proveedor::query()->with('contactos')->findOrFail($id);
        $estados = $this->estadosRelacion();
        $proveedor = $this->mapProveedorForView($proveedor);

        return view('proveedores.edit', compact('proveedor', 'estados'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado_id' => ['required', 'integer'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tiempo_entrega_dias' => ['nullable', 'integer', 'min:1', 'max:120'],
            'condiciones_pago' => ['nullable', 'string', 'max:120'],
            'calificacion' => ['nullable', 'numeric', 'between:0,5'],
        ]);

        $proveedor = Proveedor::query()->create([
            'codigo_proveedor' => $this->generarCodigoProveedor(),
            'razon_social' => $data['nombre'],
            'nombre_comercial' => $data['nombre'],
            'tipo_proveedor' => 'General',
            'direccion' => $data['direccion'] ?? null,
            'telefono_principal' => $data['telefono'] ?? null,
            'email_general' => $data['email'] ?? null,
            'estatus' => $this->estadoIdToNombre((int) $data['estado_id']),
            'dias_credito' => (int) ($data['dias_credito'] ?? 0),
            'tiempo_entrega_dias' => (int) ($data['tiempo_entrega_dias'] ?? 3),
            'condiciones_pago' => $data['condiciones_pago'] ?? null,
            'calificacion' => (float) ($data['calificacion'] ?? 0),
        ]);

        if (! empty($data['contacto'])) {
            ContactoProveedor::query()->create([
                'proveedor_id' => $proveedor->id,
                'nombre_completo' => $data['contacto'],
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
                'es_contacto_principal' => true,
            ]);
        }

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor creado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado_id' => ['required', 'integer'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tiempo_entrega_dias' => ['nullable', 'integer', 'min:1', 'max:120'],
            'condiciones_pago' => ['nullable', 'string', 'max:120'],
            'calificacion' => ['nullable', 'numeric', 'between:0,5'],
        ]);

        $proveedor = Proveedor::query()->findOrFail($id);

        $proveedor->update([
            'razon_social' => $data['nombre'],
            'nombre_comercial' => $data['nombre'],
            'direccion' => $data['direccion'] ?? null,
            'telefono_principal' => $data['telefono'] ?? null,
            'email_general' => $data['email'] ?? null,
            'estatus' => $this->estadoIdToNombre((int) $data['estado_id']),
            'dias_credito' => (int) ($data['dias_credito'] ?? 0),
            'tiempo_entrega_dias' => (int) ($data['tiempo_entrega_dias'] ?? 3),
            'condiciones_pago' => $data['condiciones_pago'] ?? null,
            'calificacion' => (float) ($data['calificacion'] ?? 0),
        ]);

        $contactoPrincipal = $proveedor->contactos()->where('es_contacto_principal', true)->first();

        if ($contactoPrincipal) {
            $contactoPrincipal->update([
                'nombre_completo' => $data['contacto'] ?: $contactoPrincipal->nombre_completo,
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
        } elseif (! empty($data['contacto'])) {
            ContactoProveedor::query()->create([
                'proveedor_id' => $proveedor->id,
                'nombre_completo' => $data['contacto'],
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
                'es_contacto_principal' => true,
            ]);
        }

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor actualizado correctamente.');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        $proveedor = Proveedor::query()->findOrFail($id);
        $estatusActual = mb_strtolower((string) $proveedor->estatus);
        $proveedor->estatus = $estatusActual === 'activo' ? 'Inactivo' : 'Activo';
        $proveedor->save();

        return back()->with('ok', 'Estado del proveedor actualizado correctamente.');
    }

    /**
     * @return Collection<int, object>
     */
    private function estadosRelacion(): Collection
    {
        return collect([
            (object) ['id' => 1, 'nombre' => 'Activo'],
            (object) ['id' => 2, 'nombre' => 'Inactivo'],
            (object) ['id' => 3, 'nombre' => 'Suspendido'],
        ]);
    }

    private function mapProveedorForView(Proveedor $proveedor): object
    {
        $contactoPrincipal = $proveedor->contactos->first();
        $estadoNombre = $this->normalizarEstado((string) ($proveedor->estatus ?? 'Activo'));

        return (object) [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre_comercial ?: $proveedor->razon_social,
            'contacto' => $contactoPrincipal?->nombre_completo,
            'email' => $proveedor->email_general,
            'telefono' => $proveedor->telefono_principal,
            'direccion' => $proveedor->direccion,
            'dias_credito' => (int) ($proveedor->dias_credito ?? 0),
            'tiempo_entrega_dias' => (int) ($proveedor->tiempo_entrega_dias ?? 3),
            'condiciones_pago' => $proveedor->condiciones_pago,
            'calificacion' => (float) ($proveedor->calificacion ?? 0),
            'estado_id' => $this->estadoNombreToId($estadoNombre),
            'estado' => (object) ['nombre' => $estadoNombre],
        ];
    }

    private function normalizarEstado(string $estatus): string
    {
        $v = mb_strtolower(trim($estatus));

        return match ($v) {
            'inactivo' => 'Inactivo',
            'suspendido' => 'Suspendido',
            default => 'Activo',
        };
    }

    private function estadoIdToNombre(int $id): string
    {
        return match ($id) {
            2 => 'Inactivo',
            3 => 'Suspendido',
            default => 'Activo',
        };
    }

    private function estadoNombreToId(string $nombre): int
    {
        $v = mb_strtolower(trim($nombre));

        return match ($v) {
            'inactivo' => 2,
            'suspendido' => 3,
            default => 1,
        };
    }

    private function generarCodigoProveedor(): string
    {
        do {
            $codigo = 'PRV-' . now()->format('ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Proveedor::query()->where('codigo_proveedor', $codigo)->exists());

        return $codigo;
    }
}
