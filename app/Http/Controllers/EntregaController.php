<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $userRole = strtoupper((string) ($user?->role?->slug ?: $user?->role?->nombre ?: 'USUARIO'));
        $roleKey = PermisoService::normalizeRoleKey((string) ($user?->role?->slug ?: $user?->role?->nombre));
        $isProveedor = $roleKey === 'PROVEEDOR';

        abort_unless(PermisoService::canAccessModule($user, 'Entregas'), 403);

        $materiales = $isProveedor ? collect() : Insumo::query()
            ->with('proveedor:id,razon_social,nombre_comercial')
            ->where('activo', true)
            ->orderBy('nombre')
            ->limit(200)
            ->get()
            ->map(fn (Insumo $material): object => (object) [
                'id' => $material->id,
                'nombre' => $material->nombre,
                'proveedor' => (object) [
                    'nombre' => $material->proveedor?->nombre_comercial ?: $material->proveedor?->razon_social,
                ],
            ]);

        $ordenes = $isProveedor ? collect() : OrdenCompra::query()
            ->with('proveedor:id,razon_social,nombre_comercial')
            ->orderByDesc('fecha_orden')
            ->limit(200)
            ->get()
            ->map(fn (OrdenCompra $orden): object => (object) [
                'id' => $orden->id,
                'fecha' => optional($orden->fecha_orden)->format('Y-m-d') ?: '-',
                'proveedor' => (object) [
                    'nombre' => $orden->proveedor?->nombre_comercial ?: $orden->proveedor?->razon_social,
                ],
            ]);

        $entregasRaw = MovimientoInventario::query()
            ->with(['insumo.proveedor:id,razon_social,nombre_comercial', 'user:id,name'])
            ->where('tipo_movimiento', MovimientoInventario::TIPO_ENTRADA)
            ->when($isProveedor && $user, function ($query) use ($user): void {
                $query->where(function ($subQuery) use ($user): void {
                    $subQuery->whereHas('insumo.proveedor', function ($proveedorQuery) use ($user): void {
                        $proveedorQuery->where('email_general', $user->email)
                            ->orWhereHas('contactos', function ($contactosQuery) use ($user): void {
                                $contactosQuery->where('email', $user->email);
                            });
                    });
                });
            })
            ->orderByDesc('fecha_movimiento')
            ->limit(300)
            ->get();

        $entregas = $entregasRaw->map(function (MovimientoInventario $mov): object {
            [$estadoRevision, $observacionRevision] = $this->parseRevision((string) $mov->motivo, (string) $mov->notas);

            return (object) [
                'id' => $mov->id,
                'proveedor' => (object) [
                    'nombre' => $mov->insumo?->proveedor?->nombre_comercial ?: $mov->insumo?->proveedor?->razon_social,
                ],
                'usuario' => (object) [
                    'nombre' => $mov->user?->name,
                ],
                'material' => (object) [
                    'nombre' => $mov->insumo?->nombre,
                ],
                'orden_compra_id' => $mov->orden_compra_id,
                'fecha_entrega' => $mov->fecha_movimiento,
                'cantidad_entregada' => (float) $mov->cantidad,
                'estado_calidad' => $mov->motivo ?: 'ACEPTADO',
                'estado_revision' => $estadoRevision,
                'observacion_revision' => $observacionRevision,
                'observaciones' => $mov->notas,
                'revisor' => null,
            ];
        });

        return view('entregas.index', compact('userRole', 'entregas', 'materiales', 'ordenes'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Entregas', 'editar'), 403);

        $data = $request->validate([
            'material_id' => ['required', 'integer', 'exists:insumos,id'],
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'fecha_entrega' => ['required', 'date'],
            'cantidad_entregada' => ['required', 'numeric', 'gt:0'],
            'estado_calidad' => ['required', 'in:ACEPTADO,OBSERVADO,RECHAZADO'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $insumo = Insumo::query()->findOrFail((int) $data['material_id']);

        DB::transaction(function () use ($insumo, $data): void {
            MovimientoInventario::query()->create([
                'tipo_movimiento' => MovimientoInventario::TIPO_ENTRADA,
                'insumo_id' => $insumo->id,
                'orden_compra_id' => $data['orden_compra_id'] ? (int) $data['orden_compra_id'] : null,
                'cantidad' => (float) $data['cantidad_entregada'],
                'unidad_medida_id' => $insumo->unidad_medida_id,
                'motivo' => (string) $data['estado_calidad'],
                'user_id' => Auth::id() ?? 1,
                'fecha_movimiento' => $data['fecha_entrega'],
                'notas' => $data['observaciones'] ?? null,
                'saldo_anterior' => (float) $insumo->stock_actual,
                'saldo_posterior' => (float) $insumo->stock_actual + (float) $data['cantidad_entregada'],
            ]);

            $insumo->stock_actual = (float) $insumo->stock_actual + (float) $data['cantidad_entregada'];
            $insumo->save();
        });

        return redirect()->route('entregas.index')->with('ok', 'Recepción registrada correctamente.');
    }

    public function revision(Request $request, int $id): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Entregas', 'editar'), 403);

        $data = $request->validate([
            'estado_revision' => ['required', 'in:APROBADO,RECHAZADO'],
            'observacion_revision' => ['nullable', 'string', 'max:500'],
        ]);

        $mov = MovimientoInventario::query()->findOrFail($id);
        $mov->motivo = 'REVISION:' . $data['estado_revision'];
        $mov->notas = trim((string) ($mov->notas . "\n" . ($data['observacion_revision'] ?? '')));
        $mov->save();

        return back()->with('ok', 'Revisión de entrega aplicada correctamente.');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parseRevision(string $motivo, string $notas): array
    {
        if (str_starts_with($motivo, 'REVISION:')) {
            return [str_replace('REVISION:', '', $motivo), trim($notas)];
        }

        return ['PENDIENTE', ''];
    }
}
