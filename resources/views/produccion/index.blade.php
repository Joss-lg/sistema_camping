@extends('layouts.app')

@section('content')
<div class="lc-page">

    @if (session('ok'))
        <div class="lc-alert lc-alert-success">
            {{ session('ok') }}
        </div>
    @endif
    
    {{-- Encabezado y Estadísticas --}}
    <!-- Encabezado arriba -->
    <div class="lc-page-header">
        <div>
        <div class="lc-kicker">Operacion dia a dia</div>
        <h1 class="lc-title mt-2">1. Produccion</h1>
        <p class="lc-subtitle mt-3 max-w-2xl">
            Primer bloque del flujo operativo: crea órdenes, cambia estado y registra consumo real de materiales.
        </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="lc-badge lc-badge-success">Flujo principal</div>
        </div>
    </div>
    <!-- Estadísticas debajo -->
    <div class="grid w-full grid-cols-2 gap-4 md:grid-cols-4">
        <div class="lc-stat-card min-w-[120px]">
            <span class="lc-stat-label">Ordenes</span>
            <div class="lc-stat-value text-2xl">{{ $statsOrdenes }}</div>
        </div>
        <div class="lc-stat-card min-w-[120px] bg-sky-50/85">
            <span class="lc-stat-label text-sky-500">En proceso</span>
            <div class="text-2xl font-bold text-blue-700">{{ $statsEnProceso }}</div>
        </div>
        <div class="lc-stat-card min-w-[120px] bg-emerald-50/85">
            <span class="lc-stat-label text-emerald-500">Finalizadas</span>
            <div class="text-2xl font-bold text-green-700">{{ $statsFinalizadas }}</div>
        </div>
        <div class="lc-stat-card min-w-[120px] bg-amber-50/85">
            <span class="lc-stat-label text-amber-600">Merma mes</span>
            <div class="text-2xl font-bold text-amber-700">{{ number_format($statsMerma, 2) }}</div>
            <div class="text-[11px] text-amber-600">{{ number_format($statsMermaPorcentaje, 2) }}%</div>
        </div>
    </div>

    {{-- Alertas de Error --}}
    @if ($errors->any())
        <div class="lc-alert lc-alert-danger">
            <div class="flex items-center gap-2 text-red-800 font-bold mb-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                Revisa los datos:
            </div>
            <ul class="list-disc list-inside text-sm text-red-700 ml-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Paneles de Gestión --}}
    @if (! $canManage)
        <div class="lc-alert lc-alert-warning flex items-center gap-4 p-6">
            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2m8-3V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-3z"></path></svg>
            <div>
                <strong class="text-lg block font-bold">Acceso limitado</strong>
                <p class="opacity-90">Solo ADMIN, SUPER ADMIN y ALMACEN pueden crear órdenes o registrar consumos.</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            {{-- Crear Orden --}}
            <section class="lc-card p-6">
                <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                    <span class="p-1.5 bg-green-100 text-green-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></span>
                    Crear orden de producción
                </h2>
                <form method="POST" action="{{ route('produccion.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="lc-field">
                            <label class="lc-label">Producto terminado</label>
                            <select name="producto_id" required class="lc-select">
                                <option value="">Selecciona</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" @selected((int) old('producto_id') === (int) $producto->id)>
                                        {{ $producto->nombre }} ({{ $producto->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Cantidad objetivo</label>
                            <input name="cantidad" type="number" min="0.01" step="0.01" value="{{ old('cantidad') }}" required class="lc-input">
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Asignar responsable (Area/Supervisor) <span class="text-red-500">*</span></label>
                            <select name="responsable_id" required class="lc-select">
                                <option value="">Selecciona un responsable</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" @selected((int) old('responsable_id') === (int) $usuario->id)>{{ $usuario->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Fecha inicio</label>
                            <input name="fecha_inicio" type="datetime-local" value="{{ old('fecha_inicio') }}" class="lc-input">
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Fecha esperada</label>
                            <input name="fecha_esperada" type="datetime-local" value="{{ old('fecha_esperada') }}" class="lc-input">
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Etapa inicial</label>
                            <select name="etapa_fabricacion_actual" class="lc-select">
                                @foreach ($etapasFabricacion as $etapaFabricacion)
                                    <option value="{{ $etapaFabricacion }}" @selected(old('etapa_fabricacion_actual', 'Corte') === $etapaFabricacion)>{{ $etapaFabricacion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Turno asignado</label>
                            <select name="turno_asignado" class="lc-select">
                                <option value="">Sin turno</option>
                                @foreach ($turnos as $turno)
                                    <option value="{{ $turno }}" @selected(old('turno_asignado') === $turno)>{{ $turno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lc-field md:col-span-2">
                            <label class="lc-label">Maquina o estacion</label>
                            <input name="maquina_asignada" type="text" value="{{ old('maquina_asignada') }}" placeholder="Ej: Máquina costura Juki #2"
                                class="lc-input">
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <input type="checkbox" id="solicitar_compra" name="solicitar_compra" value="1" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                        <label for="solicitar_compra" class="text-sm font-medium text-slate-700">Solicitar compra de materiales faltantes si no hay stock suficiente</label>
                    </div>
                    <button type="submit" class="lc-btn-primary w-full">Guardar orden</button>
                </form>
            </section>

            {{-- Registro de consumo migrado al detalle por orden --}}
            <section class="lc-card p-6 text-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-3">Registro de consumo por orden</h2>
                <p class="text-slate-600">
                    Para registrar insumos usa el botón <strong>Gestionar orden</strong> en el tablón de seguimiento.
                    Ahí se muestra la receta específica del producto y se evita mezclar materiales entre órdenes.
                </p>
            </section>
        </div>
    @endif

    {{-- Seguimiento de Órdenes --}}
        {{-- Filtro de responsables --}}
        <div class="lc-toolbar">
            <div>
                <div class="text-sm font-bold text-slate-700">Seguimiento operativo</div>
                <p class="text-xs text-slate-500">Usa el filtro para enfocarte por responsable y entra a la vista detallada de cada orden.</p>
            </div>
            <label for="filtro-responsable" class="sr-only">Filtrar por responsable</label>
            <select id="filtro-responsable" class="lc-select w-full sm:w-72">
                <option value="">Todos</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}">{{ $usuario->nombre }}</option>
                @endforeach
            </select>
            <span id="filtro-responsable-feedback" class="text-xs font-medium text-slate-500" aria-live="polite"></span>
        </div>
    <section class="lc-card overflow-hidden text-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">Seguimiento de órdenes</h2>
            <p class="lc-section-subtitle">Consulta avance, materiales y merma. La gestión completa se realiza desde el botón Gestionar orden.</p>
        </div>
        <div class="lc-table-wrap lc-scrollbar">
            <table class="lc-table min-w-[1000px]">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto / SKU</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Etapa fab.</th>
                        <th>Responsable</th>
                        <th>Maquina / Turno</th>
                        <th>Fechas</th>
                        <th class="w-64">Consumos material</th>
                        @if ($canManage)
                            <th>Actualizar</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="tabla-seguimiento-ordenes">
            @include('produccion.partials.tabla_ordenes', ['ordenes' => $ordenes, 'canManage' => $canManage, 'usuarios' => $usuarios])
        </tbody>
            </table>
        </div>
    </section>

    <script>
        document.getElementById('filtro-responsable').addEventListener('change', function() {
            const responsableId = this.value;
            const feedback = document.getElementById('filtro-responsable-feedback');
            const tbody = document.getElementById('tabla-seguimiento-ordenes');

            this.disabled = true;
            tbody.classList.add('opacity-60');
            feedback.textContent = 'Cargando órdenes...';

            fetch(`{{ route('produccion.ordenes-filtradas') }}?responsable_id=${responsableId}`)
                .then(response => response.text())
                .then(html => {
                    tbody.innerHTML = html;
                    feedback.textContent = responsableId ? 'Filtro aplicado.' : 'Mostrando todos los responsables.';
                })
                .catch(() => {
                    feedback.textContent = 'No se pudo actualizar la tabla.';
                })
                .finally(() => {
                    this.disabled = false;
                    tbody.classList.remove('opacity-60');
                });
        });
    </script>

@endsection