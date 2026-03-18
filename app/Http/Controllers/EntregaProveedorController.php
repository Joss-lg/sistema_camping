<?php

namespace App\Http\Controllers;

use App\Models\EntregaProveedor;
use App\Models\Material;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntregaProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = Usuario::find($request->session()->get('auth_user_id'));
        $proveedor = $usuario ? Proveedor::where('email', $usuario->email)->first() : null;
        $userRole = strtoupper((string) $request->session()->get('auth_user_rol', ''));

        $ordenes = collect();
        $materiales = collect();
        $entregas = collect();

        if ($proveedor) {
            // Lógica para proveedores
            $ordenes = OrdenCompra::where('proveedor_id', $proveedor->id)
                ->whereHas('estado', function ($query) {
                    $query->whereRaw('UPPER(nombre) = ?', ['PENDIENTE']);
                })
                ->orderByDesc('id')
                ->get(['id', 'fecha', 'fecha_esperada']);

            $materiales = Material::where('proveedor_id', $proveedor->id)
                ->orderBy('nombre')
                ->get(['id', 'nombre']);

            $entregas = EntregaProveedor::with(['material'])
                ->where('usuario_id', (int) $request->session()->get('auth_user_id'))
                ->orderByDesc('id')
                ->get();
        } elseif (in_array($userRole, ['ADMIN', 'ALMACEN'])) {
            // Lógica para admin y almacen - ven todo
            $ordenes = OrdenCompra::with('proveedor')
                ->whereHas('estado', function ($query) {
                    $query->whereRaw('UPPER(nombre) = ?', ['PENDIENTE']);
                })
                ->orderByDesc('id')
                ->get(['id', 'fecha', 'fecha_esperada', 'proveedor_id']);

            $materiales = Material::with('proveedor')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'proveedor_id']);

            $entregas = EntregaProveedor::with(['material', 'proveedor', 'usuario'])
                ->orderByDesc('id')
                ->get();
        }

        return view('entregas.index', [
            'proveedor' => $proveedor,
            'ordenes' => $ordenes,
            'materiales' => $materiales,
            'entregas' => $entregas,
            'userRole' => $userRole,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Usuario::find($request->session()->get('auth_user_id'));
        $proveedor = $usuario ? Proveedor::where('email', $usuario->email)->first() : null;
        $userRole = strtoupper((string) $request->session()->get('auth_user_rol', ''));

        $data = $request->validate([
            'orden_compra_id' => ['nullable', 'integer', 'exists:orden_compra,id'],
            'material_id' => ['required', 'integer', 'exists:material,id'],
            'fecha_entrega' => ['required', 'date'],
            'cantidad_entregada' => ['required', 'numeric', 'gt:0'],
            'estado_calidad' => ['required', 'in:ACEPTADO,OBSERVADO,RECHAZADO'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = Material::find((int) $data['material_id']);
        if (! $material) {
            return back()->withErrors(['material_id' => 'Material no encontrado.'])->withInput();
        }

        // Para proveedores, validar que el material les pertenece
        if ($proveedor && ! in_array($userRole, ['ADMIN', 'ALMACEN'])) {
            if ($material->proveedor_id !== $proveedor->id) {
                return back()->withErrors(['material_id' => 'El material seleccionado no pertenece a tu proveedor.'])->withInput();
            }

            if (! empty($data['orden_compra_id'])) {
                $ordenPertenece = OrdenCompra::where('id', (int) $data['orden_compra_id'])
                    ->where('proveedor_id', $proveedor->id)
                    ->exists();

                if (! $ordenPertenece) {
                    return back()->withErrors(['orden_compra_id' => 'La orden seleccionada no pertenece a tu proveedor.'])->withInput();
                }
            }
        }

        // Determinar el proveedor_id para la entrega
        $proveedorId = $proveedor ? $proveedor->id : $material->proveedor_id;

        $entrega = EntregaProveedor::create([
            'usuario_id' => (int) $request->session()->get('auth_user_id'),
            'proveedor_id' => $proveedorId,
            'orden_compra_id' => $data['orden_compra_id'] ?? null,
            'material_id' => $data['material_id'],
            'fecha_entrega' => $data['fecha_entrega'],
            'cantidad_entregada' => $data['cantidad_entregada'],
            'estado_calidad' => $data['estado_calidad'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        // La actualización de stock y el cierre de la orden de compra se realiza al aprobar la entrega
        // desde el módulo de Compras (revisión de entregas).
        return redirect()->route('entregas.index')->with('ok', 
            in_array($userRole, ['ADMIN', 'ALMACEN']) ? 
                'Recepción registrada correctamente.' : 
                'Entrega registrada correctamente.'
        );
    }
}
