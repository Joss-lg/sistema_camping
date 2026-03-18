@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Encabezado --}}
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">4. Reportes y Analítica</h1>
        <p class="text-slate-500 mt-2 max-w-3xl italic">
            Cuarto bloque del flujo: consulta indicadores por rango de fechas, monitorea el rendimiento de fabricación y exporta datos clave a CSV.
        </p>
    </div>

    {{-- Filtros de Rango --}}
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-8 overflow-hidden">
        <div class="bg-slate-50 border-b border-slate-100 px-6 py-3">
            <h2 class="text-xs font-bold text-slate-600 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filtros de Período
            </h2>
        </div>
        <form method="GET" action="{{ route('reportes.index') }}" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="flex flex-col gap-2">
                    <label for="from" class="text-xs font-bold text-slate-700 uppercase ml-1">Fecha desde</label>
                    <input id="from" name="from" type="date" value="{{ $from }}" 
                        class="w-full border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all">
                </div>
                <div class="flex flex-col gap-2">
                    <label for="to" class="text-xs font-bold text-slate-700 uppercase ml-1">Fecha hasta</label>
                    <input id="to" name="to" type="date" value="{{ $to }}" 
                        class="w-full border border-slate-300 rounded-xl p-2.5 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-lg shadow-green-200 active:scale-95 text-sm">
                        Aplicar filtros
                    </button>
                    <a href="{{ route('reportes.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm text-center">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </section>

    {{-- Grid de Estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
        @php
            $cards = [
                ['label' => 'Entregas en rango', 'val' => $statsEntregas, 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label' => 'Cantidad entregada', 'val' => number_format($statsCantidadEntregada, 2), 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['label' => 'Órdenes de producción', 'val' => $statsOrdenesProduccion, 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['label' => 'Cantidad completada', 'val' => number_format($statsCantidadCompletada, 2), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Lotes generados', 'val' => $statsLotes, 'icon' => 'M7 7h.01M7 11h.01M7 15h.01M11 7h.01M11 11h.01M11 15h.01M15 7h.01M15 11h.01M15 15h.01'],
                ['label' => 'Insumos bajo mínimo', 'val' => $statsInsumosBajo, 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'danger' => $statsInsumosBajo > 0]
            ];
        @endphp

        @foreach($cards as $card)
        <article class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="p-3 {{ ($card['danger'] ?? false) ? 'bg-red-100 text-red-600' : 'bg-blue-50 text-blue-600' }} rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path></svg>
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ $card['label'] }}</div>
                <div class="text-2xl font-black text-slate-800 leading-none mt-1">{{ $card['val'] }}</div>
            </div>
        </article>
        @endforeach
    </div>

    {{-- Secciones de Tablas --}}
    <div class="space-y-10">
        
        {{-- Reporte de Entregas --}}
        <section class="bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-slate-800 px-6 py-4 flex justify-between items-center">
                <h2 class="text-white font-bold tracking-tight">Reporte de Entregas</h2>
                <a href="{{ route('reportes.export.csv', ['type' => 'entregas', 'from' => $from, 'to' => $to]) }}" 
                   class="text-[11px] bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg font-bold uppercase transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Exportar CSV
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">ID</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Proveedor</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Material</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Cantidad</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Calidad / Rev.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 italic md:not-italic">
                        @forelse ($entregas as $entrega)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-xs font-mono text-slate-400">#{{ $entrega->id }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ optional($entrega->fecha_entrega)->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">{{ $entrega->proveedor?->nombre ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $entrega->material?->nombre ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-green-700">{{ number_format((float) $entrega->cantidad_entregada, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold uppercase border border-blue-100">{{ $entrega->estado_calidad }}</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold uppercase border border-slate-200">{{ $entrega->estado_revision }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">No hay entregas en este rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Reporte de Producción --}}
        <section class="bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-blue-900 px-6 py-4 flex justify-between items-center text-white font-bold tracking-tight">
                <h2>Rendimiento de Producción</h2>
                <a href="{{ route('reportes.export.csv', ['type' => 'produccion', 'from' => $from, 'to' => $to]) }}" 
                   class="text-[11px] bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg font-bold uppercase transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Exportar
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Orden</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Producto / SKU</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Estado</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Progreso (Completada / Objetivo)</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ordenesProduccion as $orden)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-slate-800 text-center">#{{ $orden->id }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $orden->producto?->nombre ?? '-' }}</div>
                                <div class="text-[10px] font-medium text-slate-400 uppercase tracking-tighter">{{ $orden->producto?->sku ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 text-[10px] font-black uppercase">{{ $orden->estado?->nombre ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex justify-between text-[11px] font-bold text-slate-500">
                                        <span>{{ number_format((float) $orden->cantidad_completada, 0) }} / {{ number_format((float) $orden->cantidad, 0) }}</span>
                                        @php $porcentaje = $orden->cantidad > 0 ? ($orden->cantidad_completada / $orden->cantidad) * 100 : 0; @endphp
                                        <span>{{ round($porcentaje) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-blue-600 h-full transition-all" style="width: {{ $porcentaje }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ optional($orden->created_at)->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No hay órdenes de producción.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Lotes e Insumos (Dos columnas en Desktop) --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            {{-- Lotes --}}
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="bg-slate-50 px-6 py-3 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-xs font-bold text-slate-600 uppercase tracking-widest">Últimos Lotes</h2>
                    <a href="{{ route('reportes.export.csv', ['type' => 'lotes', 'from' => $from, 'to' => $to]) }}" class="text-blue-600 text-[10px] font-bold uppercase hover:underline">Exportar</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($lotes as $lote)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-sm font-bold text-slate-700">{{ $lote->numero_lote }}</td>
                                <td class="px-6 py-3 text-sm text-slate-500">{{ $lote->producto?->nombre }}</td>
                                <td class="px-6 py-3 text-right text-xs text-slate-400 italic">{{ optional($lote->fecha_produccion)->format('d/m/y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Insumos bajo mínimo --}}
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="bg-red-50 px-6 py-3 border-b border-red-100 flex justify-between items-center">
                    <h2 class="text-xs font-bold text-red-700 uppercase tracking-widest">Alerta de Stock</h2>
                    <a href="{{ route('reportes.export.csv', ['type' => 'insumos-bajo', 'from' => $from, 'to' => $to]) }}" class="text-red-700 text-[10px] font-bold uppercase hover:underline">Exportar</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($insumosBajo as $material)
                            <tr class="bg-red-50/20">
                                <td class="px-6 py-3">
                                    <div class="text-sm font-bold text-slate-800">{{ $material->nombre }}</div>
                                    <div class="text-[10px] text-slate-500 uppercase">{{ $material->categoria?->nombre }}</div>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="text-xs font-black text-red-600">{{ number_format($material->stock, 1) }} / {{ number_format($material->stock_minimo, 0) }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-tighter">{{ $material->unidad?->nombre }}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection