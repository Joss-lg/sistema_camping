<?php

use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaProveedorController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TerminadoController;
use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/permisos/{id}/editar', [PermisoController::class, 'edit'])->name('permisos.edit');
Route::middleware('session.auth')->group(function () {
	Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
	Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
	Route::get('/insumos', [InsumoController::class, 'index'])->name('insumos.index');
	Route::get('/produccion', [ProduccionController::class, 'index'])->name('produccion.index');
	Route::get('/produccion/bom', [ProduccionController::class, 'bomIndex'])->name('produccion.bom.index');
	Route::post('/produccion', [ProduccionController::class, 'store'])->name('produccion.store');
	Route::patch('/produccion/{ordenProduccion}/estado', [ProduccionController::class, 'updateEstado'])->name('produccion.update-estado');
	Route::post('/produccion/consumos', [ProduccionController::class, 'registrarConsumo'])->name('produccion.registrar-consumo');
	Route::get('/terminados', [TerminadoController::class, 'index'])->name('terminados.index');
	Route::post('/terminados/productos', [TerminadoController::class, 'storeProducto'])->name('terminados.productos.store');
	Route::post('/terminados/ingresos', [TerminadoController::class, 'registrarIngreso'])->name('terminados.ingresos.store');
	Route::post('/terminados/ajustes', [TerminadoController::class, 'ajustarStock'])->name('terminados.ajustes.store');
	Route::get('/trazabilidad', [TrazabilidadController::class, 'index'])->name('trazabilidad.index');
	Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
	Route::get('/reportes/exportar-csv', [ReporteController::class, 'exportCsv'])->name('reportes.export.csv');
	Route::get('/entregas', [EntregaProveedorController::class, 'index'])->name('entregas.index');
	Route::post('/entregas', [EntregaProveedorController::class, 'store'])->name('entregas.store');

	Route::middleware('proveedor.only')->group(function () {
		// Rutas específicas de proveedores si las hay
	});

	Route::middleware('admin.only')->group(function () {
		Route::post('/compras/entregas/{entrega}/revision', [CompraController::class, 'revisarEntrega'])->name('compras.entregas.revision');
		Route::post('/compras/ordenes/sugeridas', [CompraController::class, 'generarOrdenSugerida'])->name('compras.ordenes.sugeridas');
		Route::post('/produccion/bom', [ProduccionController::class, 'bomStore'])->name('produccion.bom.store');
		Route::post('/insumos', [InsumoController::class, 'store'])->name('insumos.store');
		Route::put('/insumos/{material}', [InsumoController::class, 'update'])->name('insumos.update');
		Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
		Route::get('/proveedores/crear', [ProveedorController::class, 'create'])->name('proveedores.create');
		Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
		Route::get('/proveedores/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
		Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
		Route::patch('/proveedores/{proveedor}/estado', [ProveedorController::class, 'toggleEstado'])->name('proveedores.toggle-estado');
		Route::get('/permisos', [PermisoController::class, 'index'])->name('permisos.index');
		Route::post('/permisos/usuarios', [PermisoController::class, 'store'])->name('permisos.store');
		Route::put('/permisos/usuarios/{usuario}', [PermisoController::class, 'update'])->name('permisos.update');
		Route::delete('/permisos/usuarios/{usuario}', [PermisoController::class, 'destroy'])->name('permisos.destroy');
		Route::patch('/permisos/usuarios/{usuario}/estado', [PermisoController::class, 'toggleEstado'])->name('permisos.toggleEstado');
	});
});
