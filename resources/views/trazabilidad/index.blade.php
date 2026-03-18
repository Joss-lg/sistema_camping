@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header de Sección --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">3. Trazabilidad de Lotes</h1>
        </div>
        <p class="text-slate-500 max-w-3xl">
            Consulta el historial completo de cada lote. Desde su creación en producción hasta su ingreso en almacén, permitiendo auditar cada movimiento y responsable.
        </p>
    </div>

    {{-- Stats Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <article class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Total Lotes en Sistema</p>
                <p class="text-3xl font-black text-slate-800">{{ $statsLotes }}</p>
            </div>
            <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
        </article>
        <article class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Movimientos Registrados</p>
                <p class="text-3xl font-black text-indigo-600">{{ $statsMovimientos }}</p>
            </div>
            <div class="h-12 w-12 bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </article>
    </div>

    {{-- Buscador --}}
    <section class="bg-slate-900 rounded-2xl p-6 mb-8 shadow-lg">
        <form method="GET" action="{{ route('trazabilidad.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full flex flex-col gap-2">
                <label for="q" class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Filtro inteligente</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input id="q" name="q" type="text" value="{{ $q }}" 
                        placeholder="Número de lote, SKU o nombre del producto..." 
                        class="w-full bg-slate-800 border-none rounded-xl py-3 pl-10 pr-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 transition-all outline-none text-sm">
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-8 py-3 rounded-xl transition-all active:scale-95 shadow-lg shadow-indigo-900/20">
                    Buscar
                </button>
                <a href="{{ route('trazabilidad.index') }}" class="bg-slate-700 hover:bg-slate-600 text-slate-200 font-bold px-4 py-3 rounded-xl transition-all">
                    Limpiar
                </a>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Resultados de Búsqueda --}}
        <div class="lg:col-span-2">
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-sm font-black text-slate-700 uppercase tracking-widest">Lotes Encontrados</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white border-b border-slate-100">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Información del Lote</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Estado</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($lotes as $lote)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $selectedLote && $selectedLote->id == $lote->id ? 'bg-indigo-50/50' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-800">{{ $lote->numero_lote }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        {{ $lote->producto?->nombre ?? 'Sin producto' }} 
                                        <span class="text-slate-300 mx-1">|</span> 
                                        <span class="font-mono text-slate-400 uppercase">{{ $lote->producto?->sku ?? '-' }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1 italic">
                                        Prod: {{ optional($lote->fecha_produccion)->format('d M, Y') ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $lote->estado?->nombre ?? 'Indefinido' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('trazabilidad.index', ['q' => $q, 'lote_id' => $lote->id]) }}" 
                                       class="inline-flex items-center gap-2 text-xs font-bold {{ $selectedLote && $selectedLote->id == $lote->id ? 'text-indigo-600' : 'text-slate-400 hover:text-indigo-600' }} transition-colors">
                                        Ver Historial
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400 italic text-sm">
                                    No se encontraron lotes con los criterios de búsqueda.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($lotes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $lotes->appends(['q' => $q])->links() }}
                </div>
                @endif
            </section>
        </div>

        {{-- Línea de Tiempo --}}
        <div class="lg:col-span-1">
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden sticky top-8">
                <div class="px-6 py-4 border-b border-slate-100 bg-indigo-600">
                    <h2 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Línea de Tiempo Operativa
                    </h2>
                </div>
                
                <div class="p-6">
                    @if (! $selectedLote)
                        <div class="text-center py-10 px-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-dashed border-slate-200">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                            </div>
                            <p class="text-sm text-slate-400 font-medium leading-relaxed">
                                Selecciona un lote de la lista para desglosar su trayectoria.
                            </p>
                        </div>
                    @else
                        <div class="mb-6 pb-4 border-b border-slate-100">
                            <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Visualizando historial de:</div>
                            <div class="text-lg font-black text-slate-800 leading-tight">{{ $selectedLote->numero_lote }}</div>
                            <div class="text-xs text-slate-500 mt-1">{{ $selectedLote->producto?->nombre }}</div>
                        </div>

                        <div class="relative pl-6 border-l-2 border-slate-100 space-y-8">
                            @forelse ($selectedLote->pasos->sortByDesc('fecha') as $paso)
                            <div class="relative">
                                {{-- Punto de la línea --}}
                                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full border-4 border-white bg-indigo-600 shadow-sm"></div>
                                
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ optional($paso->fecha)->format('d M, Y · H:i') }}
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-tight">{{ $paso->etapa }}</h3>
                                    <p class="text-xs text-slate-500 leading-relaxed bg-slate-50 p-2 rounded-lg border border-slate-100 mt-1">
                                        {{ $paso->descripcion ?: 'Sin observaciones adicionales.' }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-2 text-[10px] font-bold text-slate-400 uppercase">
                                        <div class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[8px] text-slate-500">
                                            {{ substr($paso->usuario?->nombre ?? 'U', 0, 1) }}
                                        </div>
                                        Responsable: {{ $paso->usuario?->nombre ?? 'Sistema' }}
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-sm text-slate-400 italic py-4">
                                No hay movimientos registrados para este lote.
                            </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection