<?php

namespace App\Http\Controllers;

use App\Models\ProductoLote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrazabilidadController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $productos = \App\Models\ProductoTerminado::with(['pasosTrazabilidad.usuario'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->get();

        $selectedProductoId = (int) $request->query('producto_id', 0);
        $selectedProducto = $selectedProductoId > 0
            ? $productos->where('id', $selectedProductoId)->first()
            : ($productos->first() ?? null);

        $statsLotes = \App\Models\ProductoLote::count();
        return view('trazabilidad.index', [
            'q' => $q,
            'productos' => $productos,
            'selectedProducto' => $selectedProducto,
            'statsProductos' => $productos->count(),
            'statsMovimientos' => $selectedProducto ? $selectedProducto->pasosTrazabilidad->count() : 0,
            'statsLotes' => $statsLotes,
        ]);
    }
}
