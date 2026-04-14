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
                    ->orWhere('rfc', 'like', '%' . $q . '%')
                    ->orWhere('tipo_proveedor', 'like', '%' . $q . '%')
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
        $estatuses = $this->estatusesRelacion();

        return view('proveedores.create', compact('estatuses'));
    }

    public function edit(int $id): View
    {
        $proveedor = Proveedor::query()->with('contactos')->findOrFail($id);
        $estatuses = $this->estatusesRelacion();
        $contactoPrincipal = $proveedor->contactos->firstWhere('es_contacto_principal', true)
            ?? $proveedor->contactos->first();

        return view('proveedores.edit', compact('proveedor', 'contactoPrincipal', 'estatuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'tipo_proveedor' => ['required', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'pais' => ['nullable', 'string', 'max:100'],
            'telefono_principal' => ['nullable', 'string', 'max:20'],
            'email_general' => ['nullable', 'email', 'max:255'],
            'estatus' => ['required', 'in:Activo,Inactivo,Suspendido'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tiempo_entrega_dias' => ['nullable', 'integer', 'min:1', 'max:120'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'between:0,100'],
            'condiciones_pago' => ['nullable', 'string', 'max:120'],
            'calificacion' => ['nullable', 'numeric', 'between:0,5'],
            'certificaciones' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
            'contacto_principal' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor = Proveedor::query()->create([
            'razon_social' => $data['razon_social'],
            'nombre_comercial' => $data['nombre_comercial'] ?? null,
            'rfc' => $data['rfc'] ?? null,
            'tipo_proveedor' => $data['tipo_proveedor'],
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado' => $data['estado'] ?? null,
            'codigo_postal' => $data['codigo_postal'] ?? null,
            'pais' => $data['pais'] ?? 'México',
            'telefono_principal' => $data['telefono_principal'] ?? null,
            'email_general' => $data['email_general'] ?? null,
            'estatus' => $data['estatus'],
            'dias_credito' => (int) ($data['dias_credito'] ?? 0),
            'tiempo_entrega_dias' => (int) ($data['tiempo_entrega_dias'] ?? 3),
            'limite_credito' => (float) ($data['limite_credito'] ?? 0),
            'descuento_porcentaje' => (float) ($data['descuento_porcentaje'] ?? 0),
            'condiciones_pago' => $data['condiciones_pago'] ?? null,
            'calificacion' => (float) ($data['calificacion'] ?? 0),
            'certificaciones' => $data['certificaciones'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);

        if (! empty($data['contacto_principal'])) {
            ContactoProveedor::query()->create([
                'proveedor_id' => $proveedor->id,
                'nombre_completo' => $data['contacto_principal'],
                'telefono' => $data['telefono_principal'] ?? null,
                'email' => $data['email_general'] ?? null,
                'es_contacto_principal' => true,
            ]);
        }

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor creado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'tipo_proveedor' => ['required', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'pais' => ['nullable', 'string', 'max:100'],
            'telefono_principal' => ['nullable', 'string', 'max:20'],
            'email_general' => ['nullable', 'email', 'max:255'],
            'estatus' => ['required', 'in:Activo,Inactivo,Suspendido'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tiempo_entrega_dias' => ['nullable', 'integer', 'min:1', 'max:120'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'between:0,100'],
            'condiciones_pago' => ['nullable', 'string', 'max:120'],
            'calificacion' => ['nullable', 'numeric', 'between:0,5'],
            'certificaciones' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
            'contacto_principal' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor = Proveedor::query()->findOrFail($id);

        $proveedor->update([
            'razon_social' => $data['razon_social'],
            'nombre_comercial' => $data['nombre_comercial'] ?? null,
            'rfc' => $data['rfc'] ?? null,
            'tipo_proveedor' => $data['tipo_proveedor'],
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'estado' => $data['estado'] ?? null,
            'codigo_postal' => $data['codigo_postal'] ?? null,
            'pais' => $data['pais'] ?? 'México',
            'telefono_principal' => $data['telefono_principal'] ?? null,
            'email_general' => $data['email_general'] ?? null,
            'estatus' => $data['estatus'],
            'dias_credito' => (int) ($data['dias_credito'] ?? 0),
            'tiempo_entrega_dias' => (int) ($data['tiempo_entrega_dias'] ?? 3),
            'limite_credito' => (float) ($data['limite_credito'] ?? 0),
            'descuento_porcentaje' => (float) ($data['descuento_porcentaje'] ?? 0),
            'condiciones_pago' => $data['condiciones_pago'] ?? null,
            'calificacion' => (float) ($data['calificacion'] ?? 0),
            'certificaciones' => $data['certificaciones'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);

        $contactoPrincipal = $proveedor->contactos()->where('es_contacto_principal', true)->first();

        if ($contactoPrincipal) {
            $contactoPrincipal->update([
                'nombre_completo' => $data['contacto_principal'] ?: $contactoPrincipal->nombre_completo,
                'telefono' => $data['telefono_principal'] ?? null,
                'email' => $data['email_general'] ?? null,
            ]);
        } elseif (! empty($data['contacto_principal'])) {
            ContactoProveedor::query()->create([
                'proveedor_id' => $proveedor->id,
                'nombre_completo' => $data['contacto_principal'],
                'telefono' => $data['telefono_principal'] ?? null,
                'email' => $data['email_general'] ?? null,
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
    private function estatusesRelacion(): Collection
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
            'rfc' => $proveedor->rfc,
            'tipo_proveedor' => $proveedor->tipo_proveedor,
            'contacto' => $contactoPrincipal?->nombre_completo,
            'email' => $proveedor->email_general,
            'telefono' => $proveedor->telefono_principal,
            'direccion' => $proveedor->direccion,
            'dias_credito' => (int) ($proveedor->dias_credito ?? 0),
            'tiempo_entrega_dias' => (int) ($proveedor->tiempo_entrega_dias ?? 3),
            'limite_credito' => (float) ($proveedor->limite_credito ?? 0),
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
}
