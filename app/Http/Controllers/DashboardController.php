<?php

namespace App\Http\Controllers;

use App\Models\EntregaProveedor;
use App\Models\Material;
use App\Models\OrdenProduccion;
use App\Models\ProductoLote;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! $this->canViewModule('Dashboard')) {
            return redirect()->route('produccion.index')->with('error', 'No tienes permisos.');
        }

        $today = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];

        $kpis = [
            'entregasPendientesRevision' => EntregaProveedor::where('estado_revision', 'PENDIENTE')->count(),
            // Cambio a whereRaw para máxima compatibilidad al comparar 2 columnas
            'insumosBajoMinimo' => Material::whereRaw('stock <= stock_minimo')->count(),
            'ordenesEnProceso' => OrdenProduccion::whereHas('estado', function ($query) {
                $query->where('nombre', 'EN_PROCESO');
            })->count(),
            'lotesGeneradosHoy' => ProductoLote::whereBetween('fecha_produccion', $today)->count(),
        ];

        // Consultas optimizadas con Eager Loading
        $ultimasEntregas = EntregaProveedor::with(['proveedor:id,nombre', 'material:id,nombre'])
            ->latest('fecha_entrega')
            ->limit(6)
            ->get();

        $ultimasOrdenes = OrdenProduccion::with(['producto:id,nombre,sku', 'estado:id,nombre'])
            ->latest('id')
            ->limit(6)
            ->get();

        $ultimosLotes = ProductoLote::with(['producto:id,nombre,sku', 'estado:id,nombre'])
            ->latest('fecha_produccion')
            ->limit(6)
            ->get();

        return view('dashboard.index', [
            'kpis' => $kpis,
            'ultimasEntregas' => $ultimasEntregas,
            'ultimasOrdenes' => $ultimasOrdenes,
            'ultimosLotes' => $ultimosLotes,
            'sessionRole' => strtoupper((string) session('auth_user_rol', 'INVITADO')),
            'access' => $this->getModuleAccess(),
        ]);
    }

    private function getModuleAccess(): array
    {
        $modules = ['Dashboard', 'Compras', 'Insumos', 'Produccion', 'Terminados', 'Trazabilidad', 'Reportes', 'Proveedores'];
        $access = [];
        
        foreach ($modules as $module) {
            // Genera claves como 'dashboard', 'compras', etc.
            $key = strtolower(explode(' ', $module)[0]);
            $access[$key] = $this->canViewModule($module);
        }
        
        // Clave explícita para el permiso largo
        $access['permisos'] = $this->canViewModule('Crear usuarios y otorgar permisos');
        
        return $access;
    }
}