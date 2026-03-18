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
        $selectedLoteId = (int) $request->query('lote_id', 0);

        $lotes = ProductoLote::with([
            'producto:id,nombre,sku,unidad_id',
            'producto.unidad:id,nombre',
            'estado:id,nombre',
            'pasos.usuario:id,nombre',
        ])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested->where('numero_lote', 'like', "%{$q}%")
                        ->orWhereHas('producto', function ($productoQuery) use ($q) {
                            $productoQuery->where('nombre', 'like', "%{$q}%")
                                ->orWhere('sku', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $selectedLote = null;
        if ($selectedLoteId > 0) {
            $selectedLote = ProductoLote::with([
                'producto:id,nombre,sku,unidad_id',
                'producto.unidad:id,nombre',
                'estado:id,nombre',
                'pasos.usuario:id,nombre',
            ])->find($selectedLoteId);
        } elseif ($lotes->count() > 0) {
            $selectedLote = $lotes->first();
        }

        return view('trazabilidad.index', [
            'q' => $q,
            'lotes' => $lotes,
            'selectedLote' => $selectedLote,
            'statsLotes' => (int) ProductoLote::count(),
            'statsMovimientos' => (int) ($selectedLote?->pasos->count() ?? 0),
        ]);
    }
}
