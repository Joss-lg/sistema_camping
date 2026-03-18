<?php

namespace App\Http\Controllers;

use App\Models\EntregaProveedor;
use App\Models\Estado;
use App\Models\ItemCompra;
use App\Models\Material;
use App\Models\OrdenCompra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        if (! $this->canViewModule('Compras')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver compras.');
        }

        $isAdmin = $this->canEditModule('Compras');

        $entregas = collect();
        $insumosCriticos = collect();
        $ordenesCompra = collect();

        if ($isAdmin) {
            $entregas = EntregaProveedor::with(['proveedor', 'ordenCompra', 'usuario', 'revisor'])
                ->orderByDesc('id')
                ->get();

            $ordenesCompra = OrdenCompra::with(['proveedor', 'estado', 'usuario', 'items.material'])
                ->whereHas('estado', function ($query) {
                    $query->whereRaw('UPPER(nombre) = ?', ['PENDIENTE']);
                })
                ->orderByDesc('id')
                ->get();

            $insumosCriticos = Material::with(['proveedor', 'unidad'])
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->orderBy('stock', 'asc')
                ->get()
                ->map(function ($material) {
                    $stock = (float) $material->stock;
                    $minimo = (float) $material->stock_minimo;
                    $maximo = (float) $material->stock_maximo;

                    $faltanteMinimo = max($minimo - $stock, 0);
                    $cantidadSugerida = max($faltanteMinimo, $maximo > 0 ? ($maximo - $stock) : 0);

                    return [
                        'material' => $material,
                        'cantidad_sugerida' => max($cantidadSugerida, 1),
                    ];
                });
        }

        return view('compras.index', [
            'isAdmin' => $isAdmin,
            'entregas' => $entregas,
            'ordenesCompra' => $ordenesCompra,
            'insumosCriticos' => $insumosCriticos,
        ]);
    }

    public function generarOrdenSugerida(Request $request): RedirectResponse
    {
        if (! $this->canEditModule('Compras')) {
            return redirect()->route('compras.index')->with('error', 'No tienes permisos para generar ordenes de compra.');
        }

        $data = $request->validate([
            'material_ids' => ['required', 'array', 'min:1'],
            'material_ids.*' => ['integer', 'exists:material,id'],
        ]);

        $materiales = Material::whereIn('id', $data['material_ids'])
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->get();

        if ($materiales->isEmpty()) {
            return redirect()->route('compras.index')->with('error', 'No hay materiales criticos seleccionados.');
        }

        $materialesPorProveedor = $materiales->groupBy('proveedor_id');

        DB::transaction(function () use ($materialesPorProveedor, $request): void {
            $estadoPendiente = Estado::firstOrCreate([
                'nombre' => 'PENDIENTE',
                'tipo' => 'compra',
            ]);

            foreach ($materialesPorProveedor as $proveedorId => $items) {
                if (! $proveedorId) {
                    continue;
                }

                // Reutilizar la orden pendiente existente para evitar órdenes duplicadas
                $orden = OrdenCompra::firstOrCreate(
                    [
                        'proveedor_id' => (int) $proveedorId,
                        'estado_id' => $estadoPendiente->id,
                    ],
                    [
                        'fecha' => now(),
                        'fecha_esperada' => now()->addDays(7),
                        'usuario_id' => (int) $request->session()->get('auth_user_id'),
                    ]
                );

                foreach ($items as $material) {
                    $stock = (float) $material->stock;
                    $minimo = (float) $material->stock_minimo;
                    $maximo = (float) $material->stock_maximo;

                    $faltanteMinimo = max($minimo - $stock, 0);
                    $cantidadSugerida = max($faltanteMinimo, $maximo > 0 ? ($maximo - $stock) : 0);

                    $cantidadSugerida = max($cantidadSugerida, 1);

                    $item = ItemCompra::firstOrNew([
                        'orden_compra_id' => $orden->id,
                        'material_id' => $material->id,
                    ]);

                    // Si el item ya existe, acumulamos la cantidad sugerida
                    $item->cantidad = ($item->exists ? (float) $item->cantidad + $cantidadSugerida : $cantidadSugerida);
                    $item->precio_unitario = $item->precio_unitario ?? 0;
                    $item->save();
                }
            }
        });

        return redirect()->route('compras.index')->with('ok', 'Orden(es) de compra sugerida(s) generada(s) correctamente.');
    }

    public function revisarEntrega(Request $request, EntregaProveedor $entrega): RedirectResponse
    {
        if (! $this->canEditModule('Compras')) {
            return redirect()->route('compras.index')->with('error', 'No tienes permisos para revisar entregas.');
        }

        $data = $request->validate([
            'estado_revision' => ['required', 'in:APROBADO,RECHAZADO'],
            'observacion_revision' => ['nullable', 'string', 'max:1000'],
        ]);

        $originalEstado = strtoupper((string) $entrega->estado_revision);

        $entrega->update([
            'estado_revision' => $data['estado_revision'],
            'observacion_revision' => $data['observacion_revision'] ?? null,
            'revisado_por_usuario_id' => (int) $request->session()->get('auth_user_id'),
            'revisado_en' => now(),
        ]);

        // Si la entrega se aprueba por primera vez, actualizar stock y cerrar la orden de compra asociada
        if ($data['estado_revision'] === 'APROBADO' && $originalEstado !== 'APROBADO') {
            if (strtoupper((string) $entrega->estado_calidad) === 'ACEPTADO') {
                Material::where('id', $entrega->material_id)
                    ->increment('stock', $entrega->cantidad_entregada);
            }

            if ($entrega->orden_compra_id) {
                $ordenCompra = OrdenCompra::find($entrega->orden_compra_id);
                if ($ordenCompra) {
                    $estadoFinalizada = Estado::firstOrCreate([
                        'nombre' => 'FINALIZADA',
                        'tipo' => 'compra',
                    ]);
                    $ordenCompra->estado_id = $estadoFinalizada->id;
                    $ordenCompra->save();
                }
            }
        }

        // Si la entrega es rechazada, cancelar la orden de compra (para que deje de mostrarse en pendientes)
        if ($data['estado_revision'] === 'RECHAZADO' && $originalEstado !== 'RECHAZADO') {
            if ($entrega->orden_compra_id) {
                $ordenCompra = OrdenCompra::find($entrega->orden_compra_id);
                if ($ordenCompra) {
                    $estadoCancelada = Estado::firstOrCreate([
                        'nombre' => 'CANCELADA',
                        'tipo' => 'compra',
                    ]);
                    $ordenCompra->estado_id = $estadoCancelada->id;
                    $ordenCompra->save();
                }
            }
        }

        return redirect()->route('compras.index')->with('ok', 'Revision de entrega actualizada.');
    }
}