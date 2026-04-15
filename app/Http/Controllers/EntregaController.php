<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\UbicacionAlmacen;
use App\Services\NotificacionSistemaPatternService;
use App\Services\PermisoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $roleKey = PermisoService::normalizeRoleKey((string) ($user?->role?->slug ?: $user?->role?->nombre));
        $userRole = $roleKey !== '' ? $roleKey : 'USUARIO';
        $isProveedor = $roleKey === 'PROVEEDOR';
        $proveedorIds = $isProveedor && $user ? $this->resolveProveedorIdsForUser($user) : [];

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

        $ordenes = OrdenCompra::query()
            ->with('proveedor:id,razon_social,nombre_comercial')
            ->when($isProveedor, function ($query) use ($proveedorIds): void {
                $query->whereIn('proveedor_id', $proveedorIds ?: [0]);
            })
            ->orderByDesc('fecha_orden')
            ->limit(200)
            ->get()
            ->map(function (OrdenCompra $orden): object {
                $canViewOrden = Gate::allows('view', $orden);

                return (object) [
                    'id' => $orden->id,
                    'fecha' => optional($orden->fecha_orden)->format('Y-m-d') ?: '-',
                    'proveedor' => (object) [
                        'nombre' => $orden->proveedor?->nombre_comercial ?: $orden->proveedor?->razon_social,
                    ],
                    'url_show' => $canViewOrden
                        ? route('ordenes-compra.show', ['ordenCompra' => $orden, 'origen' => 'entregas'])
                        : null,
                ];
            });

        $ordenesRecibidas = OrdenCompra::query()
            ->with('proveedor:id,razon_social,nombre_comercial')
            ->recibidas()
            ->when($isProveedor, function ($query) use ($proveedorIds): void {
                $query->whereIn('proveedor_id', $proveedorIds ?: [0]);
            })
            ->orderByDesc('fecha_entrega_real')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(function (OrdenCompra $orden): object {
                $canViewOrden = Gate::allows('view', $orden);

                return (object) [
                    'id' => $orden->id,
                    'numero_orden' => $orden->numero_orden,
                    'proveedor' => (object) [
                        'nombre' => $orden->proveedor?->nombre_comercial ?: $orden->proveedor?->razon_social,
                    ],
                    'fecha_orden' => optional($orden->fecha_orden)->format('Y-m-d') ?: '-',
                    'fecha_entrega_real' => optional($orden->fecha_entrega_real)->format('Y-m-d') ?: '-',
                    'estado' => (string) $orden->estado,
                    'monto_total' => (float) ($orden->monto_total ?? 0),
                    'url_show' => $canViewOrden
                        ? route('ordenes-compra.show', ['ordenCompra' => $orden, 'origen' => 'entregas'])
                        : null,
                ];
            });

        return view('entregas.index', compact('userRole', 'materiales', 'ordenes', 'ordenesRecibidas'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PermisoService::canAccessModule($request->user(), 'Entregas', 'editar'), 403);

        $user = $request->user();
        $roleKey = PermisoService::normalizeRoleKey((string) ($user?->role?->slug ?: $user?->role?->nombre));
        $isProveedor = $roleKey === 'PROVEEDOR';
        $proveedorIds = $isProveedor && $user ? $this->resolveProveedorIdsForUser($user) : [];

        $data = $request->validate([
            'material_id' => ['required', 'integer', 'exists:insumos,id'],
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'fecha_entrega' => ['required', 'date'],
            'cantidad_entregada' => ['required', 'numeric', 'gt:0'],
            'estado_calidad' => ['required', 'in:ACEPTADO,OBSERVADO,RECHAZADO'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($isProveedor) {
            $materialPermitido = Insumo::query()
                ->where('id', (int) $data['material_id'])
                ->whereIn('proveedor_id', $proveedorIds ?: [0])
                ->exists();

            if (! $materialPermitido) {
                abort(403, 'No tienes permiso para registrar entregas de este material.');
            }

            if (! empty($data['orden_compra_id'])) {
                $ordenPermitida = OrdenCompra::query()
                    ->where('id', (int) $data['orden_compra_id'])
                    ->whereIn('proveedor_id', $proveedorIds ?: [0])
                    ->exists();

                if (! $ordenPermitida) {
                    abort(403, 'No tienes permiso para registrar entregas en esta orden de compra.');
                }
            }
        }

        $insumo = Insumo::query()->findOrFail((int) $data['material_id']);

        DB::transaction(function () use ($insumo, $data): void {
            $cantidad = (float) $data['cantidad_entregada'];
            $ordenCompraId = $data['orden_compra_id']
                ? (int) $data['orden_compra_id']
                : $this->resolverOrdenCompraTecnicaEntrega($insumo);

            if (! $ordenCompraId) {
                throw new \RuntimeException('No se pudo resolver una orden de compra para registrar el lote de entrega.');
            }

            MovimientoInventario::query()->create([
                'tipo_movimiento' => MovimientoInventario::TIPO_ENTRADA,
                'insumo_id' => $insumo->id,
                'orden_compra_id' => $ordenCompraId,
                'cantidad' => $cantidad,
                'unidad_medida_id' => $insumo->unidad_medida_id,
                'motivo' => (string) $data['estado_calidad'],
                'user_id' => Auth::id() ?? 1,
                'fecha_movimiento' => $data['fecha_entrega'],
                'notas' => $data['observaciones'] ?? null,
                'saldo_anterior' => (float) $insumo->stock_actual,
                'saldo_posterior' => (float) $insumo->stock_actual + $cantidad,
            ]);

            $estadoLote = LoteInsumo::estadoCalidadDesdeResultado((string) $data['estado_calidad']);

            $ubicacionId = $insumo->ubicacion_almacen_id
                ?: UbicacionAlmacen::query()->where('activo', true)->value('id');

            LoteInsumo::query()->create([
                'numero_lote' => sprintf('ENT-%d-%s', (int) $insumo->id, now()->format('YmdHisu')),
                'insumo_id' => (int) $insumo->id,
                'orden_compra_id' => $ordenCompraId,
                'proveedor_id' => $insumo->proveedor_id,
                'fecha_lote' => $data['fecha_entrega'],
                'fecha_recepcion' => $data['fecha_entrega'],
                'cantidad_recibida' => $cantidad,
                'cantidad_en_stock' => $cantidad,
                'cantidad_consumida' => 0,
                'cantidad_rechazada' => 0,
                'ubicacion_almacen_id' => $ubicacionId,
                'estado_calidad' => $estadoLote,
                'user_recepcion_id' => Auth::id(),
                'notas' => $data['observaciones'] ?? 'Lote generado automáticamente desde Entregas.',
                'activo' => true,
            ]);

            $insumo->stock_actual = (float) $insumo->stock_actual + $cantidad;
            $insumo->save();

            // Notificación informativa: se registró nuevo stock del insumo.
            $notificacionService = app(NotificacionSistemaPatternService::class);
            $destinatarios = $notificacionService->usuariosActivos();

            foreach ($destinatarios as $usuario) {
                $notificacionService->crearSiNoExisteHoy([
                    'titulo' => 'Nuevo stock registrado',
                    'mensaje' => sprintf(
                        'Se registró entrada de stock para %s (%s). Cantidad: %.2f. Stock actual: %.2f.',
                        (string) $insumo->nombre,
                        (string) $insumo->codigo_insumo,
                        $cantidad,
                        (float) $insumo->stock_actual
                    ),
                    'tipo' => 'Informativa',
                    'modulo' => 'Insumos',
                    'prioridad' => 'Media',
                    'user_id' => (int) $usuario->id,
                    'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                    'estado' => 'Pendiente',
                    'fecha_programada' => now(),
                    'requiere_accion' => false,
                    'url_accion' => '/insumos',
                    'metadata' => [
                        'tipo_alerta' => 'nuevo_stock_registrado',
                        'insumo_id' => (int) $insumo->id,
                        'codigo_insumo' => (string) $insumo->codigo_insumo,
                        'cantidad_entregada' => $cantidad,
                        'stock_actual' => (float) $insumo->stock_actual,
                        'origen' => 'entregas.store.ingreso',
                    ],
                ], 'insumo_id', (int) $insumo->id);
            }

            // Notificación crítica: aun después del ingreso sigue por debajo del mínimo.
            if ((float) $insumo->stock_actual <= (float) $insumo->stock_minimo) {
                foreach ($destinatarios as $usuario) {
                    $notificacionService->crearSiNoExisteHoy([
                        'titulo' => 'Reabastecimiento insuficiente',
                        'mensaje' => sprintf(
                            'El insumo %s (%s) sigue bajo mínimo tras la recepción. Stock actual: %.2f, mínimo: %.2f.',
                            (string) $insumo->nombre,
                            (string) $insumo->codigo_insumo,
                            (float) $insumo->stock_actual,
                            (float) $insumo->stock_minimo
                        ),
                        'tipo' => 'Alerta',
                        'modulo' => 'Compras',
                        'prioridad' => 'Alta',
                        'user_id' => (int) $usuario->id,
                        'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                        'estado' => 'Pendiente',
                        'fecha_programada' => now(),
                        'requiere_accion' => true,
                        'url_accion' => '/ordenes-compra/create',
                        'metadata' => [
                            'tipo_alerta' => 'reabastecimiento_insuficiente',
                            'insumo_id' => (int) $insumo->id,
                            'codigo_insumo' => (string) $insumo->codigo_insumo,
                            'stock_actual' => (float) $insumo->stock_actual,
                            'stock_minimo' => (float) $insumo->stock_minimo,
                            'origen' => 'entregas.store.reabastecimiento_insuficiente',
                        ],
                    ], 'insumo_id', (int) $insumo->id);
                }
            }

            if ((string) $data['estado_calidad'] === 'RECHAZADO') {
                foreach ($destinatarios as $usuario) {
                    $notificacionService->crearSiNoExisteHoy([
                        'titulo' => 'Entrega rechazada por calidad',
                        'mensaje' => sprintf(
                            'Se registró una entrega rechazada del insumo %s (%s). Cantidad: %.2f. Revisar recepción y proveedor.',
                            (string) $insumo->nombre,
                            (string) $insumo->codigo_insumo,
                            $cantidad
                        ),
                        'tipo' => 'Alerta',
                        'modulo' => 'Compras',
                        'prioridad' => 'Alta',
                        'user_id' => (int) $usuario->id,
                        'role_id' => $usuario->role_id ? (int) $usuario->role_id : null,
                        'estado' => 'Pendiente',
                        'fecha_programada' => now(),
                        'requiere_accion' => true,
                        'url_accion' => '/entregas',
                        'metadata' => [
                            'tipo_alerta' => 'entrega_rechazada_calidad',
                            'insumo_id' => (int) $insumo->id,
                            'codigo_insumo' => (string) $insumo->codigo_insumo,
                            'cantidad_entregada' => $cantidad,
                            'origen' => 'entregas.store.rechazado',
                        ],
                    ], 'insumo_id', (int) $insumo->id);
                }
            }
        });

        return redirect()->route('entregas.index')->with('ok', 'Recepción registrada correctamente.');
    }

    private function resolverOrdenCompraTecnicaEntrega(Insumo $insumo): ?int
    {
        $ordenCompraDesdeLote = LoteInsumo::query()
            ->where('insumo_id', (int) $insumo->id)
            ->whereNotNull('orden_compra_id')
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeLote) {
            return (int) $ordenCompraDesdeLote;
        }

        $ordenCompraDesdeDetalle = DB::table('ordenes_compra_detalles')
            ->where('insumo_id', (int) $insumo->id)
            ->orderByDesc('id')
            ->value('orden_compra_id');

        if ($ordenCompraDesdeDetalle) {
            return (int) $ordenCompraDesdeDetalle;
        }

        $proveedorId = (int) ($insumo->proveedor_id ?: 0);

        if ($proveedorId <= 0) {
            $proveedorId = (int) (DB::table('proveedores')->orderBy('id')->value('id') ?: 0);
        }

        if ($proveedorId <= 0) {
            return null;
        }

        $notaTecnica = 'Orden técnica para registrar entradas sin OC explícita.';
        $ordenTecnica = OrdenCompra::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'Recibida')
            ->where('notas', $notaTecnica)
            ->first();

        if ($ordenTecnica) {
            return (int) $ordenTecnica->id;
        }

        $userId = (int) (Auth::id() ?: 0);

        if ($userId <= 0) {
            $userId = (int) (DB::table('users')->orderBy('id')->value('id') ?: 0);
        }

        if ($userId <= 0) {
            return null;
        }

        $ordenNueva = OrdenCompra::query()->create([
            'proveedor_id' => $proveedorId,
            'user_id' => $userId,
            'fecha_orden' => now(),
            'fecha_entrega_prevista' => now()->toDateString(),
            'estado' => 'Recibida',
            'subtotal' => 0,
            'impuestos' => 0,
            'descuentos' => 0,
            'costo_flete' => 0,
            'monto_total' => 0,
            'notas' => $notaTecnica,
        ]);

        return (int) $ordenNueva->id;
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
     * @return array<int>
     */
    private function resolveProveedorIdsForUser($user): array
    {
        $proveedorId = (int) ($user?->proveedor_id ?? 0);

        if ($proveedorId > 0) {
            return [$proveedorId];
        }

        $email = $user?->email;

        if (! $email) {
            return [];
        }

        return DB::table('proveedores')
            ->select('proveedores.id')
            ->leftJoin('contactos_proveedores', 'contactos_proveedores.proveedor_id', '=', 'proveedores.id')
            ->where(function ($query) use ($email): void {
                $query->where('proveedores.email_general', $email)
                    ->orWhere('contactos_proveedores.email', $email);
            })
            ->distinct()
            ->pluck('proveedores.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
