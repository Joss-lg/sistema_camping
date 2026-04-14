@extends('layouts.app')

@section('content')
<div class="lc-page max-w-4xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Inventario base</div>
            <h1 class="lc-title">Crear insumo</h1>
            <p class="lc-subtitle">Registra un nuevo insumo para integrarlo al catálogo y habilitar su uso en compras, producción y control de stock.</p>
        </div>
        <a href="{{ route('insumos.index') }}" class="lc-btn-secondary">Volver</a>
    </section>

    @include('partials.flash-messages')

    <form method="POST" action="{{ route('insumos.store') }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
        @csrf

        {{-- SECCIÓN 1: IDENTIFICACIÓN --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Identificación</h2>
                    <p class="lc-section-subtitle">Código único, nombre y descripción del insumo.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="lc-field">
                        <label class="lc-label">Código <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_insumo" value="{{ old('codigo_insumo') }}"
                               placeholder="Ej: INS-001" maxlength="30" class="lc-input">
                        @error('codigo_insumo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"
                               placeholder="Ej: Tela Ripstop 100D" maxlength="150" class="lc-input">
                        @error('nombre')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Descripción técnica o de uso del insumo..." class="lc-input">{{ old('descripcion') }}</textarea>
                    @error('descripcion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- SECCIÓN 2: CLASIFICACIÓN --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Clasificación</h2>
                    <p class="lc-section-subtitle">Categoría y unidad de medida base del insumo. Escribe para buscar o crear nueva.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4" x-data="categoriaUnidadForm()">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {{-- Categoría con autocompletado --}}
                    <div class="lc-field">
                        <label class="lc-label">Categoría <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="text"
                                x-model="categoriaSearch"
                                @input="buscarCategorias()" 
                                @focus="mostrarCategorias = true"
                                @blur.debounce.500ms="mostrarCategorias = false"
                                placeholder="Busca o escribe una categoría..." 
                                class="lc-input w-full">
                            <input type="hidden" name="categoria_insumo_id" x-model="categoria_insumo_id">
                            
                            {{-- Dropdown de resultados --}}
                            <div 
                                x-show="mostrarCategorias && categoriasResultados.length > 0" 
                                class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-10">
                                <template x-for="cat in categoriasResultados" :key="cat.id">
                                    <div 
                                        @click="seleccionarCategoria(cat)"
                                        class="px-4 py-2 hover:bg-blue-100 cursor-pointer border-b last:border-b-0">
                                        <div class="font-medium" x-text="cat.nombre"></div>
                                        <div class="text-xs text-gray-600" x-text="cat.descripcion || ''"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Mostrar selección actual --}}
                            <div x-show="categoriaSeleccionada" class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                                <p class="text-sm text-green-700">
                                    ✓ Seleccionado: <span x-text="categoriaSeleccionada"></span>
                                </p>
                            </div>
                        </div>
                        @error('categoria_insumo_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Unidad de medida con autocompletado --}}
                    <div class="lc-field">
                        <label class="lc-label">Unidad de medida <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="text"
                                x-model="unidadSearch"
                                @input="buscarUnidades()" 
                                @focus="mostrarUnidades = true"
                                @blur.debounce.500ms="mostrarUnidades = false"
                                placeholder="Busca o escribe una unidad (m, kg, pz, etc)..." 
                                class="lc-input w-full">
                            <input type="hidden" name="unidad_medida_id" x-model="unidad_medida_id">
                            
                            {{-- Dropdown de resultados --}}
                            <div 
                                x-show="mostrarUnidades && unidadesResultados.length > 0" 
                                class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-10">
                                <template x-for="uni in unidadesResultados" :key="uni.id">
                                    <div 
                                        @click="seleccionarUnidad(uni)"
                                        class="px-4 py-2 hover:bg-blue-100 cursor-pointer border-b last:border-b-0">
                                        <div class="font-medium" x-text="uni.nombre_completo"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Mostrar selección actual --}}
                            <div x-show="unidadSeleccionada" class="mt-2 p-2 bg-green-50 border border-green-200 rounded">
                                <p class="text-sm text-green-700">
                                    ✓ Seleccionado: <span x-text="unidadSeleccionada"></span>
                                </p>
                            </div>
                        </div>
                        @error('unidad_medida_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCIÓN 3: PROVEEDOR --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Proveedor</h2>
                    <p class="lc-section-subtitle">Proveedor principal y código que usa en su catálogo.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="lc-field">
                        <label class="lc-label">Proveedor <span class="text-red-500">*</span></label>
                        <select name="proveedor_id" class="lc-select">
                            <option value="">Selecciona</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>
                                    {{ $proveedor->razon_social }}
                                    @if($proveedor->nombre_comercial && $proveedor->nombre_comercial !== $proveedor->razon_social)
                                        ({{ $proveedor->nombre_comercial }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('proveedor_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Código del proveedor</label>
                        <input type="text" name="codigo_proveedor_insumo" value="{{ old('codigo_proveedor_insumo') }}"
                               placeholder="Ej: TMT-TELA-01" maxlength="50" class="lc-input">
                        @error('codigo_proveedor_insumo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCIÓN 4: STOCK --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Control de stock</h2>
                    <p class="lc-section-subtitle">Cantidades iniciales y umbral mínimo para alertas de reabastecimiento.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="lc-field">
                        <label class="lc-label">Stock mínimo <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 0) }}"
                               step="0.0001" min="0" placeholder="0" class="lc-input">
                        @error('stock_minimo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Stock actual <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_actual" value="{{ old('stock_actual', 0) }}"
                               step="0.0001" min="0" placeholder="0" class="lc-input">
                        @error('stock_actual')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Stock reservado</label>
                        <input type="number" name="stock_reservado" value="{{ old('stock_reservado', 0) }}"
                               step="0.0001" min="0" placeholder="0" class="lc-input">
                        @error('stock_reservado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCIÓN 5: PRECIOS --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Precios</h2>
                    <p class="lc-section-subtitle">Precio de venta unitario y costo de adquisición.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="lc-field">
                        <label class="lc-label">Precio unitario <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">$</span>
                            <input type="number" name="precio_unitario" value="{{ old('precio_unitario') }}"
                                   step="0.0001" min="0" placeholder="0.00" class="lc-input pl-7">
                        </div>
                        @error('precio_unitario')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Precio costo</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">$</span>
                            <input type="number" name="precio_costo" value="{{ old('precio_costo') }}"
                                   step="0.0001" min="0" placeholder="0.00" class="lc-input pl-7">
                        </div>
                        @error('precio_costo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCIÓN 6: LOGÍSTICA --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Logística y almacén</h2>
                    <p class="lc-section-subtitle">Ubicación, unidad de compra y cantidad mínima de orden.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="lc-field">
                        <label class="lc-label">Ubicación en almacén</label>
                        <select name="ubicacion_almacen_id" class="lc-select">
                            <option value="">Sin asignar</option>
                            @foreach($ubicaciones as $ubicacion)
                                <option value="{{ $ubicacion->id }}" @selected(old('ubicacion_almacen_id') == $ubicacion->id)>
                                    {{ $ubicacion->codigo_ubicacion }} – {{ $ubicacion->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('ubicacion_almacen_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Unidad de compra</label>
                        <select name="unidad_compra" class="lc-select">
                            <option value="">Selecciona</option>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->abreviatura }}" @selected(old('unidad_compra', 'pz') == $unidad->abreviatura)>
                                    {{ $unidad->nombre }} ({{ $unidad->abreviatura }})
                                </option>
                            @endforeach
                        </select>
                        @error('unidad_compra')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Cantidad mínima de orden</label>
                        <input type="number" name="cantidad_minima_orden" value="{{ old('cantidad_minima_orden', 1) }}"
                               min="1" step="1" placeholder="1" class="lc-input">
                        @error('cantidad_minima_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCIÓN 7: ESTADO --}}
        <section class="lc-card">
            <div class="lc-card-header">
                <div>
                    <h2 class="lc-section-title">Estado</h2>
                    <p class="lc-section-subtitle">Disponibilidad del insumo dentro del sistema.</p>
                </div>
            </div>
            <div class="lc-card-body space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="lc-field">
                        <label class="lc-label">Estado</label>
                        <select name="estado" class="lc-select">
                            @foreach(['Activo', 'Inactivo', 'Agotado'] as $estado)
                                <option value="{{ $estado }}" @selected(old('estado', 'Activo') === $estado)>{{ $estado }}</option>
                            @endforeach
                        </select>
                        @error('estado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="lc-field justify-center">
                        <label class="lc-label">Activo en el sistema</label>
                        <label class="mt-2 flex cursor-pointer items-center gap-3">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1"
                                   @checked(old('activo', true))
                                   class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-slate-700">Habilitar para uso en compras y producción</span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        {{-- ACCIONES --}}
        <div class="flex justify-end gap-3 pb-6">
            <a href="{{ route('insumos.index') }}" class="lc-btn-secondary">Cancelar</a>
            <button type="submit" class="lc-btn-primary min-w-[160px]"
                    x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                </svg>
                <span x-text="submitting ? 'Guardando...' : 'Guardar insumo'"></span>
            </button>
        </div>
    </form>
</div>

<script>
    function categoriaUnidadForm() {
        return {
            // Categorías
            categoriaSearch: '',
            categoriasResultados: [],
            categoria_insumo_id: '',
            categoriaSeleccionada: '',
            mostrarCategorias: false,

            // Unidades
            unidadSearch: '',
            unidadesResultados: [],
            unidad_medida_id: '',
            unidadSeleccionada: '',
            mostrarUnidades: false,

            // Búsqueda de categorías
            async buscarCategorias() {
                if (this.categoriaSearch.length < 1) {
                    this.categoriasResultados = [];
                    return;
                }

                try {
                    const response = await fetch(
                        `/api/catalogs/categorias/buscar?q=${encodeURIComponent(this.categoriaSearch)}`
                    );
                    const data = await response.json();
                    this.categoriasResultados = data.data || [];
                } catch (error) {
                    console.error('Error buscando categorías:', error);
                    this.categoriasResultados = [];
                }
            },

            // Búsqueda de unidades
            async buscarUnidades() {
                if (this.unidadSearch.length < 1) {
                    this.unidadesResultados = [];
                    return;
                }

                try {
                    const response = await fetch(
                        `/api/catalogs/unidades/buscar?q=${encodeURIComponent(this.unidadSearch)}`
                    );
                    const data = await response.json();
                    this.unidadesResultados = data.data || [];
                } catch (error) {
                    console.error('Error buscando unidades:', error);
                    this.unidadesResultados = [];
                }
            },

            // Seleccionar categoría
            seleccionarCategoria(categoria) {
                this.categoria_insumo_id = categoria.id;
                this.categoriaSeleccionada = categoria.nombre;
                this.categoriaSearch = categoria.nombre;
                this.mostrarCategorias = false;
                this.categoriasResultados = [];
            },

            // Seleccionar unidad
            seleccionarUnidad(unidad) {
                this.unidad_medida_id = unidad.id;
                this.unidadSeleccionada = unidad.nombre_completo;
                this.unidadSearch = unidad.nombre_completo;
                this.mostrarUnidades = false;
                this.unidadesResultados = [];
            }
        }
    }
</script>
@endsection
