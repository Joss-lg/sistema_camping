<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use App\Models\Estado;
use App\Models\OrdenProduccion;
use App\Models\PasoTrazabilidad;
use App\Models\ProductoLote;
use App\Models\ProductoTerminado;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TerminadoController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! $this->canViewModule('Terminados')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver terminados.');
        }

        $canManage = $this->canManageTerminados();

        $categorias = CategoriaProducto::orderBy('nombre')->get(['id', 'nombre']);
        $unidades = UnidadMedida::orderBy('nombre')->get(['id', 'nombre']);

        $productos = ProductoTerminado::with(['categoria:id,nombre', 'unidad:id,nombre'])
            ->orderBy('nombre')
            ->get();

        $ordenesFinalizadas = OrdenProduccion::with(['producto:id,nombre,sku', 'estado:id,nombre'])
            ->whereHas('estado', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', ['FINALIZADA']);
            })
            ->orderByDesc('id')
            ->get();

        $lotes = ProductoLote::with([
            'producto:id,nombre,sku,unidad_id',
            'producto.unidad:id,nombre',
            'estado:id,nombre',
            'pasos.usuario:id,nombre',
        ])->orderByDesc('id')->limit(30)->get();

        return view('terminados.index', [
            'canManage' => $canManage,
            'categorias' => $categorias,
            'unidades' => $unidades,
            'productos' => $productos,
            'ordenesFinalizadas' => $ordenesFinalizadas,
            'lotes' => $lotes,
            'statsTotalProductos' => (int) $productos->count(),
            'statsStockBajo' => (int) $productos->filter(function ($producto) {
                return (float) $producto->stock <= (float) $producto->stock_minimo;
            })->count(),
            'statsLotes' => (int) ProductoLote::count(),
        ]);
    }

    public function storeProducto(Request $request): RedirectResponse
    {
        if (! $this->canManageTerminados()) {
            return redirect()->route('terminados.index')->with('error', 'No tienes permisos para crear productos.');
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('producto_terminado', 'sku')],
            'categoria_id' => ['required', 'integer', 'exists:categoria_producto,id'],
            'unidad_id' => ['required', 'integer', 'exists:unidad_medida,id'],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['required', 'numeric', 'gte:stock_minimo'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
        ]);

        $estadoActivo = Estado::firstOrCreate([
            'nombre' => 'Activo',
            'tipo' => 'producto',
        ]);

        ProductoTerminado::create([
            'nombre' => $data['nombre'],
            'sku' => $data['sku'],
            'categoria_id' => (int) $data['categoria_id'],
            'unidad_id' => (int) $data['unidad_id'],
            'stock' => (float) ($data['stock'] ?? 0),
            'stock_minimo' => (float) $data['stock_minimo'],
            'stock_maximo' => (float) $data['stock_maximo'],
            'precio_venta' => (float) $data['precio_venta'],
            'estado_id' => $estadoActivo->id,
        ]);

        return redirect()->route('terminados.index')->with('ok', 'Producto terminado creado correctamente.');
    }

    public function registrarIngreso(Request $request): RedirectResponse
    {
        if (! $this->canManageTerminados()) {
            return redirect()->route('terminados.index')->with('error', 'No tienes permisos para registrar ingresos de terminados.');
        }

        $data = $request->validate([
            'orden_produccion_id' => ['required', 'integer', 'exists:orden_produccion,id'],
            'cantidad_ingreso' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $orden = OrdenProduccion::with(['estado:id,nombre'])->lockForUpdate()->findOrFail((int) $data['orden_produccion_id']);
            $producto = ProductoTerminado::lockForUpdate()->findOrFail((int) $orden->producto_id);

            if (strtoupper((string) ($orden->estado->nombre ?? '')) !== 'FINALIZADA') {
                throw ValidationException::withMessages([
                    'orden_produccion_id' => 'Solo se permite ingreso desde ordenes FINALIZADAS.',
                ]);
            }

            $cantidadCompleta = (float) $orden->cantidad_completada;
            $cantidadIngresada = (float) ($orden->cantidad_ingresada ?? 0);
            $cantidadPendiente = max($cantidadCompleta - $cantidadIngresada, 0);
            $cantidadIngreso = (float) $data['cantidad_ingreso'];

            if ($cantidadPendiente <= 0) {
                throw ValidationException::withMessages([
                    'cantidad_ingreso' => 'Esta orden ya fue ingresada completamente a stock.',
                ]);
            }

            if ($cantidadIngreso > $cantidadPendiente) {
                throw ValidationException::withMessages([
                    'cantidad_ingreso' => 'La cantidad excede el pendiente por ingresar de la orden.',
                ]);
            }

            $producto->stock = (float) $producto->stock + $cantidadIngreso;
            $producto->save();

            $orden->cantidad_ingresada = $cantidadIngresada + $cantidadIngreso;
            $orden->save();

            $estadoLote = Estado::firstOrCreate([
                'nombre' => 'DISPONIBLE',
                'tipo' => 'lote',
            ]);

            $lote = ProductoLote::create([
                'producto_id' => $producto->id,
                'numero_lote' => 'OP'.$orden->id.'-'.now()->format('YmdHis'),
                'fecha_produccion' => now(),
                'estado_id' => $estadoLote->id,
            ]);

            PasoTrazabilidad::create([
                'lote_id' => $lote->id,
                'etapa' => 'INGRESO_TERMINADO',
                'descripcion' => 'Ingreso desde orden #'.$orden->id.' por cantidad '.number_format($cantidadIngreso, 2, '.', ''),
                'fecha' => now(),
                'usuario_id' => (int) $request->session()->get('auth_user_id'),
            ]);
        });

        return redirect()->route('terminados.index')->with('ok', 'Ingreso a stock de terminados registrado.');
    }

    public function ajustarStock(Request $request): RedirectResponse
    {
        if (! $this->canManageTerminados()) {
            return redirect()->route('terminados.index')->with('error', 'No tienes permisos para ajustar stock de terminados.');
        }

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:producto_terminado,id'],
            'tipo_ajuste' => ['required', 'in:SUMAR,RESTAR'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $producto = ProductoTerminado::lockForUpdate()->findOrFail((int) $data['producto_id']);

            $cantidad = (float) $data['cantidad'];
            $nuevoStock = (float) $producto->stock;
            if ($data['tipo_ajuste'] === 'SUMAR') {
                $nuevoStock += $cantidad;
            } else {
                $nuevoStock -= $cantidad;
            }

            if ($nuevoStock < 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'El ajuste deja stock negativo, revisa la cantidad.',
                ]);
            }

            $producto->stock = $nuevoStock;
            $producto->save();

            $estadoAjuste = Estado::firstOrCreate([
                'nombre' => 'AJUSTE',
                'tipo' => 'lote',
            ]);

            $lote = ProductoLote::create([
                'producto_id' => $producto->id,
                'numero_lote' => 'AJ'.$producto->id.'-'.now()->format('YmdHis'),
                'fecha_produccion' => now(),
                'estado_id' => $estadoAjuste->id,
            ]);

            $signo = $data['tipo_ajuste'] === 'SUMAR' ? '+' : '-';
            PasoTrazabilidad::create([
                'lote_id' => $lote->id,
                'etapa' => 'AJUSTE_STOCK',
                'descripcion' => 'Ajuste '.$signo.number_format($cantidad, 2, '.', '').'. Motivo: '.$data['motivo'],
                'fecha' => now(),
                'usuario_id' => (int) $request->session()->get('auth_user_id'),
            ]);
        });

        return redirect()->route('terminados.index')->with('ok', 'Ajuste de stock aplicado y auditado.');
    }

    private function canManageTerminados(): bool
    {
        return $this->canEditModule('Terminados');
    }
}
