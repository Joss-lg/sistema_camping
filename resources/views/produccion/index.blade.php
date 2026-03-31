@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-8">
    
    {{-- Encabezado y Estadísticas --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">1. Producción</h1>
            <p class="text-slate-500 mt-2 max-w-2xl">
                Primer bloque del flujo operativo: crea órdenes, cambia estado y registra consumo real de materiales.
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full">
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm min-w-[120px]">
                <span class="text-xs font-bold text-slate-400 uppercase">Órdenes</span>
                <div class="text-2xl font-bold text-slate-800">{{ $statsOrdenes }}</div>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 shadow-sm min-w-[120px]">
                <span class="text-xs font-bold text-blue-400 uppercase">En Proceso</span>
                <div class="text-2xl font-bold text-blue-700">{{ $statsEnProceso }}</div>
            </div>
            <div class="bg-green-50 border border-green-100 rounded-xl p-4 shadow-sm min-w-[120px]">
                <span class="text-xs font-bold text-green-400 uppercase">Finalizadas</span>
                <div class="text-2xl font-bold text-green-700">{{ $statsFinalizadas }}</div>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 shadow-sm min-w-[120px]">
                <span class="text-xs font-bold text-amber-500 uppercase">Merma Mes</span>
                <div class="text-2xl font-bold text-amber-700">{{ number_format($statsMerma, 2) }}</div>
                <div class="text-[11px] text-amber-600">{{ number_format($statsMermaPorcentaje, 2) }}%</div>
            </div>
        </div>
    </div>

    {{-- Alertas de Error --}}
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
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
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-800 flex items-center gap-4">
            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2m8-3V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-3z"></path></svg>
            <div>
                <strong class="text-lg block font-bold">Acceso limitado</strong>
                <p class="opacity-90">Solo ADMIN, SUPER ADMIN y ALMACEN pueden crear órdenes o registrar consumos.</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            {{-- Crear Orden --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                    <span class="p-1.5 bg-green-100 text-green-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg></span>
                    Crear orden de producción
                </h2>
                <form method="POST" action="{{ route('produccion.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Producto terminado</label>
                            <select name="producto_id" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="">Selecciona</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" @selected((int) old('producto_id') === (int) $producto->id)>
                                        {{ $producto->nombre }} ({{ $producto->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Cantidad objetivo</label>
                            <input name="cantidad" type="number" min="0.01" step="0.01" value="{{ old('cantidad') }}" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Asignar Responsable (Área/Supervisor) <span class="text-red-500">*</span></label>
                            <select name="responsable_id" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="">Selecciona un responsable</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" @selected((int) old('responsable_id') === (int) $usuario->id)>{{ $usuario->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Fecha inicio</label>
                            <input name="fecha_inicio" type="datetime-local" value="{{ old('fecha_inicio') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Fecha esperada</label>
                            <input name="fecha_esperada" type="datetime-local" value="{{ old('fecha_esperada') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Etapa inicial</label>
                            <select name="etapa_fabricacion_actual" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                                @foreach ($etapasFabricacion as $etapaFabricacion)
                                    <option value="{{ $etapaFabricacion }}" @selected(old('etapa_fabricacion_actual', 'Corte') === $etapaFabricacion)>{{ $etapaFabricacion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase">Turno asignado</label>
                            <select name="turno_asignado" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="">Sin turno</option>
                                @foreach ($turnos as $turno)
                                    <option value="{{ $turno }}" @selected(old('turno_asignado') === $turno)>{{ $turno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5 md:col-span-2">
                            <label class="text-xs font-bold text-slate-500 uppercase">Máquina o estación</label>
                            <input name="maquina_asignada" type="text" value="{{ old('maquina_asignada') }}" placeholder="Ej: Máquina costura Juki #2"
                                class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="solicitar_compra" name="solicitar_compra" value="1" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                        <label for="solicitar_compra" class="text-sm font-medium text-slate-700">Solicitar compra de materiales faltantes si no hay stock suficiente</label>
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-lg transition-all shadow-md">Guardar orden</button>
                </form>
            </section>

            {{-- Registrar Consumo --}}
            <section class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm text-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                    <span class="p-1.5 bg-blue-100 text-blue-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg></span>
                    Registrar consumo de material
                </h2>
                <form id="form-registrar-consumo" method="POST" action="{{ route('produccion.registrar-consumo') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase">Orden</label>
                            <select name="orden_produccion_id" required class="border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Selecciona</option>
                                @foreach ($ordenes as $orden)
                                    <option value="{{ $orden->id }}" @selected((int) old('orden_produccion_id') === (int) $orden->id) @disabled($orden->bloqueada_aprobacion)>
                                        #{{ $orden->id }} - {{ $orden->producto?->nombre }}
                                        @if($orden->bloqueada_aprobacion)
                                            (Bloqueada por aprobacion)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-amber-700 mt-1">
                                Las ordenes con etapa en Esperando Aprobacion no permiten registrar consumo hasta firma.
                            </p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase">Material</label>
                            <select name="material_id" required class="border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Selecciona</option>
                                @foreach ($materiales as $material)
                                    <option value="{{ $material->id }}" @selected((int) old('material_id') === (int) $material->id)>{{ $material->nombre }} (Stock: {{ number_format($material->stock, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase italic">Cant. Necesaria (Opt)</label>
                            <input name="cantidad_necesaria" type="number" step="0.01" value="{{ old('cantidad_necesaria') }}" class="border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase text-blue-600">Cant. Usada</label>
                            <input name="cantidad_usada" type="number" step="0.01" required value="{{ old('cantidad_usada') }}" class="border border-blue-200 bg-blue-50 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase text-amber-600 italic">Merma (Opt)</label>
                            <input name="cantidad_merma" type="number" step="0.01" value="{{ old('cantidad_merma', 0) }}" class="border border-amber-200 bg-amber-50 rounded-lg p-2 focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase italic">Motivo Merma</label>
                            <input name="motivo_merma" type="text" value="{{ old('motivo_merma') }}" placeholder="..." class="border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="font-bold text-slate-600 text-[11px] uppercase italic">Tipo Merma</label>
                            <select name="tipo_merma" class="border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none text-xs bg-white">
                                <option value="">Selecciona</option>
                                @foreach($tiposMerma as $tipoMerma)
                                    <option value="{{ $tipoMerma }}" @selected(old('tipo_merma') === $tipoMerma)>{{ $tipoMerma }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg shadow-sm">Registrar consumo</button>
                </form>
            </section>
        </div>
    @endif

    {{-- Seguimiento de Órdenes --}}
        {{-- Filtro de responsables --}}
        <div class="flex justify-end items-center mb-4">
            <label for="filtro-responsable" class="mr-2 text-sm font-bold text-slate-600">Filtrar por Responsable:</label>
            <select id="filtro-responsable" class="border border-slate-300 rounded-lg p-2 text-sm bg-white">
                <option value="">Todos</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}">{{ $usuario->nombre }}</option>
                @endforeach
            </select>
        </div>
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden text-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">Seguimiento de órdenes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 border-b border-slate-100">
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">ID</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Producto / SKU</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Progreso</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Estado</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Etapa Fab.</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Responsable</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Máquina / Turno</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px]">Fechas</th>
                        <th class="p-4 font-bold uppercase tracking-tighter text-[10px] w-64">Consumos Material</th>
                        @if ($canManage)
                            <th class="p-4 font-bold uppercase tracking-tighter text-[10px] bg-slate-100/50">Actualizar</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="tabla-seguimiento-ordenes" class="divide-y divide-slate-100">
            @include('produccion.partials.tabla_ordenes', ['ordenes' => $ordenes, 'canManage' => $canManage, 'usuarios' => $usuarios])
        </tbody>
            </table>
        </div>
    </section>

    <script>
        document.getElementById('filtro-responsable').addEventListener('change', function() {
            const responsableId = this.value;
            fetch(`{{ route('produccion.ordenes-filtradas') }}?responsable_id=${responsableId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('tabla-seguimiento-ordenes').innerHTML = html;
                });
        });
    </script>

@endsection