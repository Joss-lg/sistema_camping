<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\OrdenProduccionController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TerminadoController;
use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\UsuarioController;
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

    // Gestión de usuarios (sin show)
    Route::resource('usuarios', UsuarioController::class)->except(['show']);
    Route::patch('usuarios/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])
        ->name('usuarios.toggle-activo');

    // Permisos
    Route::get('permisos', [PermisoController::class, 'index'])->name('permisos.index');
    Route::post('permisos', [PermisoController::class, 'store'])->name('permisos.store');
    Route::patch('permisos/{id}/toggle-estado', [PermisoController::class, 'toggleEstado'])->name('permisos.toggleEstado');
    Route::put('permisos/usuarios/{id}', [PermisoController::class, 'update'])->name('permisos.usuarios.update');
    Route::delete('permisos/usuarios/{id}', [PermisoController::class, 'destroy'])->name('permisos.usuarios.destroy');

    // Proveedores
    Route::get('proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('proveedores/create', [ProveedorController::class, 'create'])->name('proveedores.create');
    Route::get('proveedores/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedores.edit');
    Route::post('proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
    Route::put('proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
    Route::patch('proveedores/{id}/toggle-estado', [ProveedorController::class, 'toggleEstado'])->name('proveedores.toggle-estado');

    // Producción (módulo operativo)
    Route::get('produccion', [ProduccionController::class, 'index'])->name('produccion.index');
    Route::post('produccion', [ProduccionController::class, 'store'])->name('produccion.store');
    Route::post('produccion/registrar-consumo', [ProduccionController::class, 'registrarConsumo'])->name('produccion.registrar-consumo');
    Route::patch('produccion/{id}/update-estado', [ProduccionController::class, 'updateEstado'])->name('produccion.update-estado');
    Route::patch('produccion/{id}/asignacion', [ProduccionController::class, 'updateAsignacion'])->name('produccion.update-asignacion');
    Route::patch('produccion/{id}/cancelar', [ProduccionController::class, 'cancelar'])->name('produccion.cancelar');
    Route::get('produccion/ordenes-filtradas', [ProduccionController::class, 'ordenesFiltradas'])->name('produccion.ordenes-filtradas');

    // Producción BOM
    Route::get('produccion/bom', [ProduccionController::class, 'bomIndex'])->name('produccion.bom.index');
    Route::post('produccion/bom', [ProduccionController::class, 'bomStore'])->name('produccion.bom.store');

    // Entregas
    Route::get('entregas', [EntregaController::class, 'index'])->name('entregas.index');
    Route::post('entregas', [EntregaController::class, 'store'])->name('entregas.store');
    Route::post('compras/entregas/{id}/revision', [EntregaController::class, 'revision'])->name('compras.entregas.revision');

    // Terminados
    Route::get('terminados', [TerminadoController::class, 'index'])->name('terminados.index');
    Route::post('terminados/ingresos', [TerminadoController::class, 'storeIngreso'])->name('terminados.ingresos.store');
    Route::post('terminados/ajustes', [TerminadoController::class, 'storeAjuste'])->name('terminados.ajustes.store');
});