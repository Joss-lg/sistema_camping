@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-slate-100">Editar insumo</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Actualiza la identificación, clasificación, stock, precios y estado del insumo.</p>
        </div>
        <a href="{{ route('insumos.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-4 py-2.5 rounded-xl font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver
        </a>
    </div>

    @include('partials.flash-messages')

    <form method="POST" action="{{ route('insumos.update', $insumo) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 shadow-sm">
            <div class="mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Identificación</h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Información base y detalles técnicos del insumo.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="codigo_insumo" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Código de insumo</label>
                    <input id="codigo_insumo" type="text" name="codigo_insumo" value="{{ old('codigo_insumo', $insumo->codigo_insumo) }}" placeholder="Ej: INS-001" required class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    @error('codigo_insumo')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="nombre" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nombre del insumo</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $insumo->nombre) }}" placeholder="Ej: Tela Ripstop 100D" required class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    @error('nombre')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 flex flex-col gap-2">
                    <label for="descripcion" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">{{ old('descripcion', $insumo->descripcion) }}</textarea>
                    @error('descripcion')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 flex flex-col gap-2">
                    <label for="especificaciones_tecnicas" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Especificaciones técnicas</label>
                    <textarea id="especificaciones_tecnicas" name="especificaciones_tecnicas" rows="3" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">{{ old('especificaciones_tecnicas', $insumo->especificaciones_tecnicas) }}</textarea>
                    @error('especificaciones_tecnicas')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 shadow-sm">
            <div class="mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Clasificación y proveedor</h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Asocia la categoría, unidad y proveedor principal.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="categoria_insumo_id" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Categoría</label>
                    <select id="categoria_insumo_id" name="categoria_insumo_id" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Selecciona</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) old('categoria_insumo_id', $insumo->categoria_insumo_id) === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_insumo_id')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="unidad_medida_id" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Unidad de medida</label>
                    <select id="unidad_medida_id" name="unidad_medida_id" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Selecciona</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad->id }}" @selected((string) old('unidad_medida_id', $insumo->unidad_medida_id) === (string) $unidad->id)>
                                {{ $unidad->nombre }}{{ $unidad->abreviatura ? ' (' . $unidad->abreviatura . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('unidad_medida_id')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="proveedor_id" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Proveedor</label>
                    <select id="proveedor_id" name="proveedor_id" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Selecciona</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected((string) old('proveedor_id', $insumo->proveedor_id) === (string) $proveedor->id)>
                                {{ $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="codigo_proveedor_insumo" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Código del proveedor</label>
                    <input id="codigo_proveedor_insumo" type="text" name="codigo_proveedor_insumo" value="{{ old('codigo_proveedor_insumo', $insumo->codigo_proveedor_insumo) }}" placeholder="Código usado por el proveedor" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
                    @error('codigo_proveedor_insumo')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 shadow-sm">
            <div class="mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Inventario, precios y estado</h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Controla existencias, costos, ubicación y disponibilidad en el sistema.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="stock_minimo" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Stock mínimo</label>
                    <input id="stock_minimo" type="number" step="0.0001" min="0" name="stock_minimo" value="{{ old('stock_minimo', $insumo->stock_minimo) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('stock_minimo')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="stock_actual" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Stock actual</label>
                    <input id="stock_actual" type="number" step="0.0001" min="0" name="stock_actual" value="{{ old('stock_actual', $insumo->stock_actual) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('stock_actual')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="stock_reservado" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Stock reservado</label>
                    <input id="stock_reservado" type="number" step="0.0001" min="0" name="stock_reservado" value="{{ old('stock_reservado', $insumo->stock_reservado) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('stock_reservado')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="precio_unitario" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Precio unitario</label>
                    <input id="precio_unitario" type="number" step="0.0001" min="0" name="precio_unitario" value="{{ old('precio_unitario', $insumo->precio_unitario) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('precio_unitario')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="precio_costo" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Precio costo</label>
                    <input id="precio_costo" type="number" step="0.0001" min="0" name="precio_costo" value="{{ old('precio_costo', $insumo->precio_costo) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('precio_costo')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="estado" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Estado</label>
                    <select id="estado" name="estado" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        @foreach(['Activo', 'Inactivo', 'Agotado'] as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $insumo->estado ?? 'Activo') === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="unidad_compra" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Unidad de compra</label>
                    <input id="unidad_compra" type="text" name="unidad_compra" value="{{ old('unidad_compra', $insumo->unidad_compra) }}" placeholder="Ej: pz, rollo, kg" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('unidad_compra')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="cantidad_minima_orden" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Cantidad mínima de orden</label>
                    <input id="cantidad_minima_orden" type="number" min="1" step="1" name="cantidad_minima_orden" value="{{ old('cantidad_minima_orden', $insumo->cantidad_minima_orden) }}" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('cantidad_minima_orden')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="ubicacion_almacen_id" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Ubicación de almacén</label>
                    <select id="ubicacion_almacen_id" name="ubicacion_almacen_id" class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">Sin asignar</option>
                        @foreach($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}" @selected((string) old('ubicacion_almacen_id', $insumo->ubicacion_almacen_id) === (string) $ubicacion->id)>
                                {{ $ubicacion->codigo_ubicacion }} – {{ $ubicacion->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('ubicacion_almacen_id')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2 lg:col-span-3 flex flex-col gap-2">
                    <label for="imagen_url" class="text-sm font-semibold text-slate-700 dark:text-slate-200">URL de imagen</label>
                    <input id="imagen_url" type="text" name="imagen_url" value="{{ old('imagen_url', $insumo->imagen_url) }}" placeholder="https://..." class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    @error('imagen_url')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-8 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Activo en el sistema</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Define si este insumo queda disponible para compras y producción.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span id="activoBadge" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ old('activo', $insumo->activo) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                            {{ old('activo', $insumo->activo) ? 'Activo' : 'Inactivo' }}
                        </span>

                        <label class="group relative inline-flex cursor-pointer items-center">
                            <input type="hidden" name="activo" value="0">
                            <input id="activoSwitch" type="checkbox" name="activo" value="1" class="peer sr-only" @checked(old('activo', $insumo->activo))>
                            <div class="h-7 w-14 rounded-full bg-slate-300 shadow-inner transition-all duration-300 peer-checked:bg-emerald-500 dark:bg-slate-600 dark:peer-checked:bg-emerald-400"></div>
                            <div class="absolute left-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[10px] font-bold text-slate-500 shadow-md transition-all duration-300 peer-checked:translate-x-7 peer-checked:text-emerald-600 dark:bg-slate-100">●</div>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('insumos.index') }}" class="bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold px-5 py-2.5 rounded-xl transition-colors">Cancelar</a>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                <span class="flex items-center gap-2 justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Actualizar insumo
                </span>
            </button>
        </div>
    </form>
</div>

<script>
    const activoSwitch = document.getElementById('activoSwitch');
    const activoBadge = document.getElementById('activoBadge');
    const estadoSelect = document.getElementById('estado');

    function refreshActivoUi(enabled) {
        if (!activoBadge) return;

        activoBadge.textContent = enabled ? 'Activo' : 'Inactivo';
        activoBadge.className = enabled
            ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
            : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
    }

    if (activoSwitch) {
        activoSwitch.addEventListener('change', () => {
            const enabled = activoSwitch.checked;
            refreshActivoUi(enabled);

            if (estadoSelect) {
                if (!enabled) {
                    estadoSelect.value = 'Inactivo';
                } else if (estadoSelect.value === 'Inactivo') {
                    estadoSelect.value = 'Activo';
                }
            }
        });
    }

    if (estadoSelect && activoSwitch) {
        estadoSelect.addEventListener('change', () => {
            if (estadoSelect.value === 'Inactivo') {
                activoSwitch.checked = false;
                refreshActivoUi(false);
            } else {
                activoSwitch.checked = true;
                refreshActivoUi(true);
            }
        });
    }
</script>
@endsection
