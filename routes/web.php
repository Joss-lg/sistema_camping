<?php

use App\Http\Controllers\Api\CatalogNormalizationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalidadMaterialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\NotificacionSistemaController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\OrdenProduccionController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PlantillaEtapaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TerminadoController;
use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\UbicacionAlmacenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas y Redirecciones
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('bienvenida');
})->name('bienvenida');

// Redirecciones útiles
Route::redirect('/bienvenida', '/');
Route::redirect('/bienvenido', '/');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

/*
|--------------------------------------------------------------------------
| Rutas con Sesión Activa
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Producción (CRUD principal)
    Route::resource('ordenes-produccion', OrdenProduccionController::class)
        ->parameters(['ordenes-produccion' => 'ordenProduccion']);
    Route::patch('ordenes-produccion/{ordenProduccion}/iniciar', [OrdenProduccionController::class, 'iniciar'])
        ->name('ordenes-produccion.iniciar');
    Route::patch('ordenes-produccion/{ordenProduccion}/completar', [OrdenProduccionController::class, 'completar'])
        ->name('ordenes-produccion.completar');

    // Insumos (CRUD SSR)
    Route::resource('insumos', InsumoController::class);
    Route::get('insumos-bajo-stock', [InsumoController::class, 'bajoStock'])
        ->name('insumos.bajo-stock');

    // Compras (CRUD principal)
    Route::resource('ordenes-compra', OrdenCompraController::class)
        ->parameters(['ordenes-compra' => 'ordenCompra']);
    Route::get('ordenes-compra/{ordenCompra}/pdf', [OrdenCompraController::class, 'pdf'])
        ->name('ordenes-compra.pdf');
    Route::patch('ordenes-compra/{ordenCompra}/aprobar', [OrdenCompraController::class, 'aprobar'])
        ->name('ordenes-compra.aprobar');
    Route::patch('ordenes-compra/{ordenCompra}/recibir', [OrdenCompraController::class, 'recibir'])
        ->name('ordenes-compra.recibir');

    // Trazabilidad SSR
    Route::get('trazabilidad', [TrazabilidadController::class, 'index'])->name('trazabilidad.index');
    Route::get('trazabilidad/{codigo}', [TrazabilidadController::class, 'show'])->name('trazabilidad.show');
    Route::patch('trazabilidad/etapas/{etapaId}/aprobar', [TrazabilidadController::class, 'aprobarEtapa'])
        ->name('trazabilidad.etapas.aprobar');

    // Reportes SSR
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');

    // Exportación de reportes
    Route::get('reportes/export/csv', [ReporteController::class, 'exportCsv'])->name('reportes.export.csv');

    // Notificaciones del sistema
    Route::get('notificaciones', [NotificacionSistemaController::class, 'index'])->name('notificaciones.index');
    Route::get('notificaciones/resumen', [NotificacionSistemaController::class, 'resumen'])->name('notificaciones.resumen');
    Route::get('notificaciones/archivadas', [NotificacionSistemaController::class, 'archivadas'])->name('notificaciones.archivadas');
    Route::patch('notificaciones/{id}/marcar-leida', [NotificacionSistemaController::class, 'marcarLeida'])->name('notificaciones.marcar-leida');
    Route::patch('notificaciones/{id}/archivar', [NotificacionSistemaController::class, 'archivar'])->name('notificaciones.archivar');
    Route::patch('notificaciones/{id}/restaurar', [NotificacionSistemaController::class, 'restaurar'])->name('notificaciones.restaurar');

    // Permisos
    Route::get('permisos', [PermisoController::class, 'index'])->name('permisos.index');
    Route::post('permisos', [PermisoController::class, 'store'])->name('permisos.store');
    Route::patch('permisos/{id}/toggle-estado', [PermisoController::class, 'toggleEstado'])->name('permisos.toggleEstado');
    Route::get('permisos/usuarios/{id}/edit', [PermisoController::class, 'edit'])->name('permisos.usuarios.edit');
    Route::put('permisos/usuarios/{id}', [PermisoController::class, 'update'])->name('permisos.usuarios.update');
    Route::delete('permisos/usuarios/{id}', [PermisoController::class, 'destroy'])->name('permisos.usuarios.destroy');

    // Proveedores
    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
    Route::get('proveedores/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
    Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::put('proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::patch('proveedores/{id}/toggle-estado', [ProveedorController::class, 'toggleEstado'])->name('proveedores.toggle-estado');

    // Almacenes (ubicaciones)
    Route::get('almacenes', [UbicacionAlmacenController::class, 'index'])->name('almacenes.index');
    Route::get('almacenes/create', [UbicacionAlmacenController::class, 'create'])->name('almacenes.create');
    Route::post('almacenes', [UbicacionAlmacenController::class, 'store'])->name('almacenes.store');
    Route::get('almacenes/{id}/edit', [UbicacionAlmacenController::class, 'edit'])->name('almacenes.edit');
    Route::put('almacenes/{id}', [UbicacionAlmacenController::class, 'update'])->name('almacenes.update');
    Route::patch('almacenes/{id}/toggle-estado', [UbicacionAlmacenController::class, 'toggleEstado'])->name('almacenes.toggle-estado');

    // Producción (módulo operativo)
    Route::get('produccion', [ProduccionController::class, 'index'])->name('produccion.index');
    Route::post('produccion', [ProduccionController::class, 'store'])->name('produccion.store');
    Route::get('produccion/{id}/seguimiento', [ProduccionController::class, 'seguimiento'])->name('produccion.seguimiento');
    Route::post('produccion/registrar-consumo', [ProduccionController::class, 'registrarConsumo'])->name('produccion.registrar-consumo');
    Route::patch('produccion/{id}/seguimiento', [ProduccionController::class, 'updateSeguimiento'])->name('produccion.update-seguimiento');
    Route::match(['post', 'patch'], 'produccion/{id}/cancelar', [ProduccionController::class, 'cancelar'])->name('produccion.cancelar');
    Route::get('produccion/ordenes-filtradas', [ProduccionController::class, 'ordenesFiltradas'])->name('produccion.ordenes-filtradas');

    // Producción BOM
    Route::get('produccion/bom', [ProduccionController::class, 'bomIndex'])->name('produccion.bom.index');
    Route::get('produccion/bom/{id}/edit', [ProduccionController::class, 'bomEdit'])->name('produccion.bom.edit');
    Route::post('produccion/bom', [ProduccionController::class, 'bomStore'])->name('produccion.bom.store');
    Route::put('produccion/bom/{id}', [ProduccionController::class, 'bomUpdate'])->name('produccion.bom.update');
    Route::patch('produccion/bom/{id}/toggle-estado', [ProduccionController::class, 'bomToggleEstado'])->name('produccion.bom.toggle-estado');

    // Producción plantillas de etapas
    Route::get('produccion/plantillas', [PlantillaEtapaController::class, 'index'])->name('produccion.plantillas.index');
    Route::post('produccion/plantillas', [PlantillaEtapaController::class, 'store'])->name('produccion.plantillas.store');

    // Entregas
    Route::get('entregas', [EntregaController::class, 'index'])->name('entregas.index');
    Route::post('entregas', [EntregaController::class, 'store'])->name('entregas.store');
    Route::post('compras/entregas/{id}/revision', [EntregaController::class, 'revision'])->name('compras.entregas.revision');
    Route::get('calidad-material', [CalidadMaterialController::class, 'index'])->name('calidad-material.index');
    Route::post('calidad-material', [CalidadMaterialController::class, 'store'])->name('calidad-material.store');

    // Terminados
    Route::get('terminados', [TerminadoController::class, 'index'])->name('terminados.index');
    Route::post('terminados/ingresos', [TerminadoController::class, 'storeIngreso'])->name('terminados.ingresos.store');
    Route::post('terminados/ventas', [TerminadoController::class, 'storeVenta'])->name('terminados.ventas.store');
    Route::post('terminados/ajustes', [TerminadoController::class, 'storeAjuste'])->name('terminados.ajustes.store');
    Route::patch('terminados/{productoTerminado}/revision', [TerminadoController::class, 'revisionCalidad'])->name('terminados.revision');

    // API - Normalización de catálogos (para autocompletado en formularios)
    Route::prefix('api/catalogs')->name('api.catalogs.')->group(function () {
        Route::get('categorias/buscar', [CatalogNormalizationController::class, 'buscarCategorias'])->name('categorias.buscar');
        Route::get('unidades/buscar', [CatalogNormalizationController::class, 'buscarUnidades'])->name('unidades.buscar');
        Route::post('categorias/normalizar', [CatalogNormalizationController::class, 'normalizarCategoria'])->name('categorias.normalizar');
        Route::post('unidades/normalizar', [CatalogNormalizationController::class, 'normalizarUnidad'])->name('unidades.normalizar');
    });
});