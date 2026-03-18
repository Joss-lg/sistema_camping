<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\ItemCompra;
use App\Models\Material;
use App\Models\OrdenCompra;
use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;
use App\Models\Proveedor;
use App\Models\UsoMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\RecetaMaterial;

class ProduccionController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! $this->canViewModule('Produccion')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver produccion.');
        }

        $canManage = $this->canManageProduccion();

        $ordenes = OrdenProduccion::with([
            'producto:id,nombre,sku',
            'estado:id,nombre',
            'usuario:id,nombre',
            'usosMaterial.material:id,nombre,stock,unidad_id',
            'usosMaterial.material.unidad:id,nombre',
        ])->orderByDesc('id')->get();

        return view('produccion.index', [
            'canManage' => $canManage,
            'productos' => ProductoTerminado::orderBy('nombre')->get(['id', 'nombre', 'sku']),
            'materiales' => Material::orderBy('nombre')->get(['id', 'nombre', 'stock', 'unidad_id']),
            'ordenes' => $ordenes,
            'statsOrdenes' => (int) OrdenProduccion::count(),
            'statsEnProceso' => (int) OrdenProduccion::whereHas('estado', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', ['EN_PROCESO']);
            })->count(),
            'statsFinalizadas' => (int) OrdenProduccion::whereHas('estado', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', ['FINALIZADA']);
            })->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canManageProduccion()) {
            return redirect()->route('produccion.index')->with('error', 'No tienes permisos para crear ordenes de produccion.');
        }

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:producto_terminado,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_esperada' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'solicitar_compra' => ['nullable', 'boolean'],
        ]);

        $estadoPendiente = Estado::firstOrCreate([
            'nombre' => 'PENDIENTE',
            'tipo' => 'produccion',
        ]);

        $productoId = (int) $data['producto_id'];
        $cantidadObjetivo = (float) $data['cantidad'];

        $receta = RecetaMaterial::with('material:id,nombre,stock,proveedor_id')
            ->where('producto_id', $productoId)
            ->where('activo', true)
            ->get();

        if ($receta->isEmpty()) {
            return back()->withErrors([
                'producto_id' => 'Este producto no tiene materiales base configurados. Debes registrarlos antes de crear la orden.',
            ])->withInput();
        }

        $faltantes = [];
        foreach ($receta as $linea) {
            $factorMerma = 1 + ((float) $linea->merma_porcentaje / 100);
            $cantidadNecesaria = $cantidadObjetivo * (float) $linea->cantidad_base * $factorMerma;
            $stockDisponible = (float) ($linea->material?->stock ?? 0);

            if ($stockDisponible < $cantidadNecesaria) {
                $cantidadFaltante = $cantidadNecesaria - $stockDisponible;
                $faltantes[] = [
                    'material' => $linea->material,
                    'cantidad_necesaria' => $cantidadNecesaria,
                    'cantidad_faltante' => $cantidadFaltante,
                ];
            }
        }

        if (! empty($faltantes)) {
            $solicitarCompra = (bool) ($data['solicitar_compra'] ?? false);
            if (! $solicitarCompra) {
                $faltantesStr = [];
                foreach ($faltantes as $faltante) {
                    $faltantesStr[] = $faltante['material']->nombre.' (disp: '.number_format($faltante['material']->stock, 2).', req: '.number_format($faltante['cantidad_necesaria'], 2).')';
                }
                return back()->withErrors([
                    'producto_id' => 'No hay stock suficiente para esta orden: '.implode(' | ', $faltantesStr),
                ])->withInput();
            }

            // Crear órdenes de compra para materiales faltantes
            $this->crearOrdenesCompra($faltantes, $request);
        }

        $orden = OrdenProduccion::create([
            'producto_id' => $productoId,
            'cantidad' => $cantidadObjetivo,
            'cantidad_completada' => 0,
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_esperada' => $data['fecha_esperada'] ?? null,
            'estado_id' => $estadoPendiente->id,
            'usuario_id' => (int) $request->session()->get('auth_user_id'),
        ]);

        foreach ($receta as $linea) {
            $factorMerma = 1 + ((float) $linea->merma_porcentaje / 100);
            $cantidadNecesaria = $cantidadObjetivo * (float) $linea->cantidad_base * $factorMerma;

            UsoMaterial::updateOrCreate(
                [
                    'orden_produccion_id' => $orden->id,
                    'material_id' => (int) $linea->material_id,
                ],
                [
                    'cantidad_necesaria' => $cantidadNecesaria,
                    'cantidad_usada' => 0,
                ]
            );
        }

        $mensaje = 'Orden de produccion creada correctamente.';
        if (! empty($faltantes)) {
            $mensaje .= ' Se han creado órdenes de compra para los materiales faltantes.';
        }

        return redirect()->route('produccion.index')->with('ok', $mensaje);
    }

    private function crearOrdenesCompra(array $faltantes, Request $request): void
    {
        $materialesPorProveedor = collect($faltantes)->groupBy(function ($item) {
            return $item['material']->proveedor_id;
        });

        DB::transaction(function () use ($materialesPorProveedor, $request): void {
            $estadoPendiente = Estado::firstOrCreate([
                'nombre' => 'PENDIENTE',
                'tipo' => 'compra',
            ]);

            foreach ($materialesPorProveedor as $proveedorId => $items) {
                if (! $proveedorId) {
                    continue;
                }

                $orden = OrdenCompra::create([
                    'proveedor_id' => (int) $proveedorId,
                    'fecha' => now(),
                    'fecha_esperada' => now()->addDays(7),
                    'estado_id' => $estadoPendiente->id,
                    'usuario_id' => (int) $request->session()->get('auth_user_id'),
                ]);

                foreach ($items as $item) {
                    ItemCompra::create([
                        'orden_compra_id' => $orden->id,
                        'material_id' => $item['material']->id,
                        'cantidad' => $item['cantidad_faltante'],
                        'precio_unitario' => 0, // Se puede actualizar después
                    ]);
                }
            }
        });
    }

    public function bomIndex(): View|RedirectResponse
    {
        if (! $this->canViewModule('Produccion')) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver ordenes de produccion.');
        }

        $canManage = $this->canManageProduccion();

        $recetas = RecetaMaterial::with([
            'producto:id,nombre,sku',
            'material:id,nombre,stock,unidad_id',
            'material.unidad:id,nombre',
        ])->orderByDesc('id')->get();

        return view('produccion.bom', [
            'canManage' => $canManage,
            'productos' => ProductoTerminado::orderBy('nombre')->get(['id', 'nombre', 'sku']),
            'materiales' => Material::orderBy('nombre')->get(['id', 'nombre', 'stock', 'unidad_id']),
            'recetas' => $recetas,
        ]);
    }

    public function bomStore(Request $request): RedirectResponse
    {
        if (! $this->canManageProduccion()) {
            return redirect()->route('produccion.bom.index')->with('error', 'No tienes permisos para gestionar ordenes de produccion.');
        }

        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:producto_terminado,id'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['required', 'integer', 'exists:material,id'],
            'cantidad_base' => ['required', 'array', 'min:1'],
            'cantidad_base.*' => ['required', 'numeric', 'gt:0'],
            'merma_porcentaje' => ['nullable', 'array'],
            'merma_porcentaje.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'activo' => ['nullable', 'array'],
            'activo.*' => ['nullable', 'boolean'],
        ]);

        $materialIds = (array) $data['material_id'];
        $cantidades = (array) $data['cantidad_base'];
        $mermas = $data['merma_porcentaje'] ?? [];
        $activos = $data['activo'] ?? [];

        foreach ($materialIds as $idx => $materialId) {
            $cantidad = $cantidades[$idx] ?? 0;
            $merma = $mermas[$idx] ?? 0;
            $activo = array_key_exists($idx, $activos) ? (bool) $activos[$idx] : true;

            RecetaMaterial::updateOrCreate(
                [
                    'producto_id' => (int) $data['producto_id'],
                    'material_id' => (int) $materialId,
                ],
                [
                    'cantidad_base' => (float) $cantidad,
                    'merma_porcentaje' => (float) $merma,
                    'activo' => $activo,
                ]
            );
        }

        return redirect()->route('produccion.bom.index')
            ->with('ok', 'Linea de materiales guardada correctamente.')
            ->withInput($request->only('producto_id'));
    }

    public function updateEstado(Request $request, OrdenProduccion $ordenProduccion): RedirectResponse
    {
        if (! $this->canManageProduccion()) {
            return redirect()->route('produccion.index')->with('error', 'No tienes permisos para actualizar estados de produccion.');
        }

        $data = $request->validate([
            'estado' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADA'],
            'cantidad_completada' => ['nullable', 'numeric', 'min:0'],
        ]);

        $estado = Estado::firstOrCreate([
            'nombre' => $data['estado'],
            'tipo' => 'produccion',
        ]);

        $payload = [
            'estado_id' => $estado->id,
        ];

        if (array_key_exists('cantidad_completada', $data) && $data['cantidad_completada'] !== null) {
            $payload['cantidad_completada'] = min((float) $data['cantidad_completada'], (float) $ordenProduccion->cantidad);
        }

        $ordenProduccion->update($payload);

        return redirect()->route('produccion.index')->with('ok', 'Estado de la orden actualizado.');
    }

    public function registrarConsumo(Request $request): RedirectResponse
    {
        if (! $this->canManageProduccion()) {
            return redirect()->route('produccion.index')->with('error', 'No tienes permisos para registrar consumo de materiales.');
        }

        $data = $request->validate([
            'orden_produccion_id' => ['required', 'integer', 'exists:orden_produccion,id'],
            'material_id' => ['required', 'integer', 'exists:material,id'],
            'cantidad_usada' => ['required', 'numeric', 'gt:0'],
            'cantidad_necesaria' => ['nullable', 'numeric', 'gt:0'],
            'cantidad_merma' => ['nullable', 'numeric', 'min:0'],
            'motivo_merma' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data): void {
            $material = Material::lockForUpdate()->findOrFail((int) $data['material_id']);
            $cantidadUsada = (float) $data['cantidad_usada'];
            $cantidadMerma = array_key_exists('cantidad_merma', $data) && $data['cantidad_merma'] !== null
                ? (float) $data['cantidad_merma']
                : 0.0;

            if ($cantidadMerma > 0 && trim((string) ($data['motivo_merma'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'motivo_merma' => 'Debes indicar un motivo cuando registras merma.',
                ]);
            }

            $cantidadSalidaTotal = $cantidadUsada + $cantidadMerma;

            if ((float) $material->stock < $cantidadSalidaTotal) {
                throw ValidationException::withMessages([
                    'cantidad_usada' => 'Stock insuficiente para registrar consumo y merma.',
                ]);
            }

            $usoMaterial = UsoMaterial::lockForUpdate()->firstOrNew([
                'orden_produccion_id' => (int) $data['orden_produccion_id'],
                'material_id' => (int) $data['material_id'],
            ]);

            $usoActual = (float) ($usoMaterial->cantidad_usada ?? 0);
            $mermaActual = (float) ($usoMaterial->cantidad_merma ?? 0);
            $necesariaActual = (float) ($usoMaterial->cantidad_necesaria ?? 0);
            $nuevaUsada = $usoActual + $cantidadUsada;
            $nuevaMerma = $mermaActual + $cantidadMerma;

            $necesariaInput = array_key_exists('cantidad_necesaria', $data) && $data['cantidad_necesaria'] !== null
                ? (float) $data['cantidad_necesaria']
                : 0;

            $nuevaNecesaria = max($necesariaActual, $necesariaInput, $nuevaUsada + $nuevaMerma);

            $usoMaterial->cantidad_necesaria = $nuevaNecesaria;
            $usoMaterial->cantidad_usada = $nuevaUsada;
            $usoMaterial->cantidad_merma = $nuevaMerma;

            if ($cantidadMerma > 0) {
                $usoMaterial->motivo_merma = trim((string) ($data['motivo_merma'] ?? ''));
            }

            $usoMaterial->save();

            $material->stock = (float) $material->stock - $cantidadSalidaTotal;
            $material->save();
        });

        return redirect()->route('produccion.index')->with('ok', 'Consumo y merma registrados. Stock actualizado.');
    }

    private function canManageProduccion(): bool
    {
        return $this->canEditModule('Produccion');
    }

}
