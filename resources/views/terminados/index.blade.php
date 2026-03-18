@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Encabezado --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">2. Gestión de Terminados</h1>
            <p class="text-slate-500 mt-2 max-w-2xl italic">
                Registra ingresos desde órdenes finalizadas, gestiona el catálogo de productos finales y aplica ajustes auditados de stock.
            </p>
        </div>
        {{-- Badge de estado rápido --}}
        <div class="flex gap-2">
            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold self-center border border-green-200">
                Flujo: Producción → Almacén
            </span>
        </div>
    </div>

    {{-- Dashboard de Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <article class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 transition-transform hover:scale-[1.02]">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Catálogo Productos</div>
                <div class="text-2xl font-black text-slate-800 leading-none mt-1">{{ $statsTotalProductos }}</div>
            </div>
        </article>

        <article class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 transition-transform hover:scale-[1.02]">
            <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest text-red-500">Stock Crítico</div>
                <div class="text-2xl font-black text-slate-800 leading-none mt-1">{{ $statsStockBajo }}</div>
            </div>
        </article>

        <article class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 transition-transform hover:scale-[1.02]">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Lotes Activos</div>
                <div class="text-2xl font-black text-slate-800 leading-none mt-1">{{ $statsLotes }}</div>
            </div>
        </article>
    </div>

    {{-- Mensajes de Error --}}
    @if ($errors->any())
        <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-md">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <strong class="text-red-800">Se encontraron errores:</strong>
            </div>
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1 ml-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Secciones de Gestión --}}
    @if ($canManage)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            {{-- Crear Producto --}}
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-tighter">Nuevo Producto Terminado</h2>
                </div>
                <form method="POST" action="{{ route('terminados.productos.store') }}" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
                    @csrf
                    <div class="md:col-span-2 flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Nombre del Producto</label>
                        <input name="nombre" type="text" value="{{ old('nombre') }}" required class="border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 outline-none">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">SKU</label>
                        <input name="sku" type="text" value="{{ old('sku') }}" required class="border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 outline-none">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Categoría</label>
                        <select name="categoria_id" required class="border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 outline-none bg-white">
                            <option value="">Selecciona</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Stock Inicial</label>
                        <input name="stock" type="number" step="0.01" value="{{ old('stock', 0) }}" class="border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 outline-none font-mono">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Stock Mínimo</label>
                        <input name="stock_minimo" type="number" step="0.01" value="{{ old('stock_minimo', 0) }}" class="border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-red-500/10 outline-none font-mono text-red-600 font-bold">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-green-100 active:scale-95">
                            Guardar Producto en Catálogo
                        </button>
                    </div>
                </form>
            </section>

            <div class="space-y-6">
                {{-- Ingreso desde Orden --}}
                <section class="bg-slate-800 border border-slate-700 rounded-2xl shadow-xl overflow-hidden p-6 text-white">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                        Ingreso desde Producción
                    </h2>
                    <form method="POST" action="{{ route('terminados.ingresos.store') }}" class="space-y-4">
                        @csrf
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Seleccionar Orden Finalizada</label>
                            <select name="orden_produccion_id" required class="bg-slate-900 border border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none text-white">
                                <option value="">Selecciona una orden...</option>
                                @foreach ($ordenesFinalizadas as $orden)
                                    @php $pendiente = max((float) $orden->cantidad_completada - (float) ($orden->cantidad_ingresada ?? 0), 0); @endphp
                                    <option value="{{ $orden->id }}" @selected(old('orden_produccion_id') == $orden->id)>
                                        #{{ $orden->id }} - {{ $orden->producto?->nombre }} (Pendiente: {{ number_format($pendiente, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4 items-end">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cantidad Real</label>
                                <input name="cantidad_ingreso" type="number" step="0.01" required class="bg-slate-900 border border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none text-white font-mono">
                            </div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-4 rounded-xl transition-all active:scale-95">
                                Registrar Entrada
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Ajuste Manual --}}
                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4">Ajuste de Auditoría</h2>
                    <form method="POST" action="{{ route('terminados.ajustes.store') }}" class="grid grid-cols-2 gap-4">
                        @csrf
                        <div class="col-span-2 flex flex-col gap-1 text-xs">
                            <label class="font-bold text-slate-500 uppercase ml-1">Producto</label>
                            <select name="producto_id" required class="border border-slate-300 rounded-xl p-2.5 outline-none bg-slate-50">
                                <option value="">Selecciona producto...</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">
                                        {{ $producto->nombre }} (Stock: {{ number_format($producto->stock, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <select name="tipo_ajuste" required class="border border-slate-300 rounded-xl p-2.5 text-xs font-bold uppercase bg-white">
                                <option value="SUMAR">➕ Sumar</option>
                                <option value="RESTAR">➖ Restar</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <input name="cantidad" type="number" step="0.01" placeholder="Cant." required class="border border-slate-300 rounded-xl p-2.5 text-sm font-mono outline-none">
                        </div>
                        <div class="col-span-2">
                            <textarea name="motivo" placeholder="Motivo del ajuste (Ej: Merma, Auditoría)..." required class="w-full border border-slate-300 rounded-xl p-2.5 text-xs h-16 outline-none"></textarea>
                        </div>
                        <button type="submit" class="col-span-2 bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 rounded-xl transition-all text-xs uppercase tracking-widest">
                            Aplicar Ajuste Auditado
                        </button>
                    </form>
                </section>
            </div>
        </div>
    @endif

    {{-- Inventario Detallado --}}
    <section class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden mb-10">
        <div class="bg-slate-900 px-8 py-5 flex justify-between items-center">
            <h2 class="text-white font-black tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                Inventario General de Terminados
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Producto / SKU</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Categoría</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Unidad</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Stock Actual</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Límites (Mín/Máx)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($productos as $producto)
                    @php $isLow = $producto->stock <= $producto->stock_minimo; @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors {{ $isLow ? 'bg-red-50/30' : '' }}">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ $producto->nombre }}</div>
                            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-tighter">{{ $producto->sku }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">{{ $producto->categoria?->nombre ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-slate-500">{{ $producto->unidad?->nombre ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-lg font-black {{ $isLow ? 'text-red-600' : 'text-slate-800' }}">
                                {{ number_format((float) $producto->stock, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1 items-center">
                                <div class="w-full max-w-[100px] bg-slate-200 h-1.5 rounded-full overflow-hidden flex">
                                    <div class="bg-blue-500 h-full" style="width: {{ min(($producto->stock / max($producto->stock_maximo, 1)) * 100, 100) }}%"></div>
                                </div>
                                <div class="flex justify-between w-full max-w-[100px] text-[9px] font-bold uppercase text-slate-400 tracking-tighter">
                                    <span>Min: {{ number_format($producto->stock_minimo, 0) }}</span>
                                    <span>Max: {{ number_format($producto->stock_maximo, 0) }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">No hay productos terminados registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Auditoría de Lotes --}}
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Auditoría de Lotes y Movimientos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Lote</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase text-center">Estado</th>
                        <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Último Paso / Actividad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lotes as $lote)
                        @php $ultimoPaso = $lote->pasos->sortByDesc('fecha')->first(); @endphp
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-slate-700">{{ $lote->numero_lote }}</div>
                                <div class="text-[10px] text-slate-400">{{ optional($lote->fecha_produccion)->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                    {{ $lote->estado?->nombre ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($ultimoPaso)
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800 uppercase">{{ $ultimoPaso->etapa }}</span>
                                        <p class="text-[11px] text-slate-500 leading-tight">{{ $ultimoPaso->descripcion }}</p>
                                        <span class="text-[9px] text-slate-400 mt-1 uppercase font-medium">
                                            Por: {{ $ultimoPaso->usuario?->nombre }} · {{ $ultimoPaso->fecha->diffForHumans() }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic italic">Sin movimientos registrados</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection