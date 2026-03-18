<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsumoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->canViewModule('Insumos')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver insumos.');
        }

        $query = trim((string) $request->query('q', ''));
        $proveedorId = (int) $request->query('proveedor_id', 0);
        $categoriaId = (int) $request->query('categoria_id', 0);
        $estadoStock = (string) $request->query('estado_stock', '');

        $materialesQuery = Material::with(['categoria', 'unidad', 'proveedor'])
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where('nombre', 'like', "%{$query}%");
            })
            ->when($proveedorId > 0, function ($builder) use ($proveedorId) {
                $builder->where('proveedor_id', $proveedorId);
            })
            ->when($categoriaId > 0, function ($builder) use ($categoriaId) {
                $builder->where('categoria_id', $categoriaId);
            })
            ->when($estadoStock === 'bajo', function ($builder) {
                $builder->whereColumn('stock', '<=', 'stock_minimo');
            })
            ->when($estadoStock === 'ok', function ($builder) {
                $builder->whereColumn('stock', '>', 'stock_minimo');
            })
            ->orderByDesc('id');

        $materiales = $materialesQuery->paginate(12)->withQueryString();
        $canManage = in_array(strtoupper((string) session('auth_user_rol', '')), ['ADMIN', 'ALMACEN'], true);

        return view('insumos.index', [
            'materiales' => $materiales,
            'proveedores' => Proveedor::orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => CategoriaMaterial::orderBy('nombre')->get(['id', 'nombre']),
            'unidades' => UnidadMedida::orderBy('nombre')->get(['id', 'nombre']),
            'statsTotal' => (int) Material::count(),
            'statsBajoMinimo' => (int) Material::whereColumn('stock', '<=', 'stock_minimo')->count(),
            'statsSinStock' => (int) Material::where('stock', '<=', 0)->count(),
            'canManage' => $canManage,
            'q' => $query,
            'selectedProveedor' => $proveedorId,
            'selectedCategoria' => $categoriaId,
            'selectedEstadoStock' => $estadoStock,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canManageInsumos()) {
            return redirect()->route('insumos.index')->with('error', 'No tienes permisos para crear insumos.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria_id' => ['required', 'integer', 'exists:categoria_material,id'],
            'unidad_id' => ['required', 'integer', 'exists:unidad_medida,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedor,id'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['required', 'numeric', 'gte:stock_minimo'],
        ]);

        Material::create($data);

        return redirect()->route('insumos.index')->with('ok', 'Insumo creado correctamente.');
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        if (! $this->canManageInsumos()) {
            return redirect()->route('insumos.index')->with('error', 'No tienes permisos para editar insumos.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria_id' => ['required', 'integer', 'exists:categoria_material,id'],
            'unidad_id' => ['required', 'integer', 'exists:unidad_medida,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedor,id'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['required', 'numeric', 'gte:stock_minimo'],
        ]);

        $material->update($data);

        return redirect()->route('insumos.index')->with('ok', 'Insumo actualizado correctamente.');
    }

    private function canManageInsumos(): bool
    {
        return $this->canEditModule('Insumos');
    }
}
