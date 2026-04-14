@extends('layouts.app')

@section('content')
<div class="lc-page">
    {{-- Header de Sección --}}
    <div class="lc-page-header">
        <div>
            <div class="lc-kicker">Auditoria operacional</div>
            <h1 class="lc-title mt-2">3. Trazabilidad de Producción</h1>
        <p class="lc-subtitle mt-3 max-w-3xl">
            Consulta el historial completo de cada orden, desde su creación en producción hasta la aprobación del producto terminado, permitiendo auditar cada movimiento y responsable.
        </p>
        </div>
        <div class="lc-badge lc-badge-neutral">Consulta historica</div>
    </div>

    {{-- Stats Rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <article class="lc-stat-card flex items-center justify-between">
            <div>
                <p class="lc-stat-label">Total órdenes monitoreadas</p>
                <p class="text-3xl font-black text-slate-800">{{ $statsProductos }}</p>
            </div>
            <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
        </article>
        <article class="lc-stat-card flex items-center justify-between">
            <div>
                <p class="lc-stat-label">Movimientos registrados</p>
                <p class="text-3xl font-black text-indigo-600">{{ $statsMovimientos }}</p>
            </div>
            <div class="h-12 w-12 bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </article>
    </div>

    {{-- Buscador --}}
    <section class="overflow-hidden rounded-3xl border border-slate-900/90 bg-slate-900 shadow-[0_24px_60px_-30px_rgba(15,23,42,0.8)]">
        <div class="border-b border-slate-800/80 px-6 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-300">Buscador inteligente</h2>
            <p class="mt-1 text-sm text-slate-400">Encuentra lotes, series y productos sin navegar por varias pantallas.</p>
        </div>
        <div class="p-6">
        <form method="GET" action="{{ route('trazabilidad.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full flex flex-col gap-2">
                <label for="q" class="text-xs font-bold text-slate-400 uppercase tracking-wider ml-1">Filtro inteligente</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input id="q" name="q" type="text" value="{{ $q }}" 
                        placeholder="Número de orden, lote, serie o nombre del producto..." 
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
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Resultados de Búsqueda --}}
        <div class="lg:col-span-2">
            <section class="lc-card overflow-hidden">
                <div class="lc-card-header bg-slate-50/70">
                    <div>
                        <h2 class="lc-section-title">Órdenes encontradas</h2>
                        <p class="lc-section-subtitle">Selecciona un registro para abrir su linea de tiempo operativa.</p>
                    </div>
                </div>
                <div class="lc-table-wrap lc-scrollbar">
                    <table class="lc-table min-w-[620px]">
                        <thead>
                            <tr>
                                <th>Información del registro</th>
                                <th class="text-center">Estado</th>
                                <th class="text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ordenes as $orden)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $selectedRegistro && $selectedRegistro->id == $orden->id ? 'bg-indigo-50/50' : '' }}">
                                <td>
                                    <div class="text-sm font-bold text-slate-800">{{ $orden->nombre }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        <span class="font-mono text-slate-400 uppercase">{{ $orden->numero_orden }}</span>
                                        <span class="mx-1">·</span>
                                        <span>{{ $orden->referencia }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="lc-badge lc-badge-neutral">
                                        {{ $orden->estado ?? 'Indefinido' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('trazabilidad.index', ['q' => $q, 'orden_id' => $orden->id]) }}" 
                                       class="inline-flex items-center gap-2 text-xs font-bold {{ $selectedRegistro && $selectedRegistro->id == $orden->id ? 'text-indigo-600' : 'text-slate-400 hover:text-indigo-600' }} transition-colors">
                                        Ver historial
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400 italic text-sm">
                                    No se encontraron órdenes con los criterios de búsqueda.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Paginación de productos si aplica --}}
                {{-- @if($productos->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $productos->appends(['q' => $q])->links() }}
                </div>
                @endif --}}
            </section>
        </div>

        {{-- Línea de Tiempo --}}
        <div class="lg:col-span-1">
            <section class="lc-card overflow-hidden sticky top-8">
                <div class="px-6 py-4 border-b border-slate-100 bg-indigo-600">
                    <h2 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Línea de Tiempo Operativa
                    </h2>
                </div>
                
                <div class="p-6">
                    @if (! $selectedRegistro)
                        <div class="text-center py-10 px-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-dashed border-slate-200">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                            </div>
                            <p class="text-sm text-slate-400 font-medium leading-relaxed">
                                Selecciona una orden de la lista para desglosar su trayectoria.
                            </p>
                        </div>
                    @else
                        <div class="mb-6 pb-4 border-b border-slate-100">
                            <div class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Visualizando historial de:</div>
                            <div class="text-lg font-black text-slate-800 leading-tight">{{ $selectedRegistro->nombre }}</div>
                            <div class="text-xs text-slate-500 mt-1">Orden: {{ $selectedRegistro->numero_orden }} · Referencia: {{ $selectedRegistro->referencia }}</div>
                        </div>

                        <div class="mb-6 flex items-center justify-between gap-3">
                            <h2 class="text-base font-bold text-slate-800">Línea de tiempo por producto</h2>
                            <span class="text-xs text-slate-500">Estado actual: {{ $selectedRegistro->estado_orden }}</span>
                        </div>

                        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            Coincidencia activa: paso {{ $selectedRegistro->linea_tiempo->paso_actual }} de {{ $selectedRegistro->linea_tiempo->total_pasos }}.
                            Fuente:
                            @if($selectedRegistro->linea_tiempo->fuente === 'trazabilidad')
                                etapas de trazabilidad configuradas para este producto.
                            @else
                                secuencia base de fabricación (fallback).
                            @endif
                        </div>

                        <div class="relative space-y-8 border-l-2 border-slate-100 pl-6">
                            @forelse ($selectedRegistro->timeline as $paso)
                            <div class="relative">
                                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full border-4 border-white bg-indigo-600 shadow-sm"></div>

                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        {{ optional($paso->fecha)->format('d M, Y · H:i') }}
                                    </span>

                                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-tight">{{ $paso->nombre }}</h3>

                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider {{ in_array($paso->estado, ['Esperando Aprobacion', 'Esperando Aprobación']) ? 'border-amber-200 bg-amber-100 text-amber-700' : (in_array($paso->estado, ['Aprobado', 'Aceptada']) ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : (in_array($paso->estado, ['Rechazado', 'Rechazada']) ? 'border-rose-200 bg-rose-100 text-rose-700' : 'border-slate-200 bg-slate-100 text-slate-600')) }}">
                                            {{ $paso->estado }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-600">
                                            {{ $paso->tipo }}
                                        </span>
                                    </div>

                                    <p class="mt-1 rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs leading-relaxed text-slate-500">
                                        {{ $paso->notas }}
                                    </p>

                                    @if ($paso->tipo === 'etapa' && in_array($paso->estado, ['Esperando Aprobacion', 'Esperando Aprobación']))
                                        <div class="mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                            Bloqueada hasta aprobación.
                                        </div>

                                        @can('aprobar', $paso->modelo)
                                            <form method="POST" action="{{ route('trazabilidad.etapas.aprobar', ['etapaId' => $paso->etapa_id]) }}" class="mt-3">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors">
                                                    Aprobar Recepción
                                                </button>
                                            </form>
                                        @else
                                            <div class="mt-2 text-[11px] text-slate-500">
                                                Solo un rol autorizado puede aprobar esta etapa.
                                            </div>
                                        @endcan
                                    @endif

                                    @if ($paso->tipo === 'producto' && ($paso->estado === 'Pendiente Inspección') && \App\Services\PermisoService::canAccessModule(auth()->user(), 'Terminados', 'editar'))
                                        <div class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            <form method="POST" action="{{ route('terminados.revision', ['productoTerminado' => $selectedRegistro->producto_terminado_id]) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea name="observaciones_calidad" rows="2" placeholder="Observaciones de calidad (opcional)" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500">{{ old('decision') === 'APROBADO' ? old('observaciones_calidad') : '' }}</textarea>
                                                <button type="submit" name="decision" value="APROBADO" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors">
                                                    Aprobar producto
                                                </button>
                                            </form>

                                            <details class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                                                <summary class="cursor-pointer text-xs font-bold text-rose-700">Rechazar producto y regresar a etapa</summary>
                                                <form method="POST" action="{{ route('terminados.revision', ['productoTerminado' => $selectedRegistro->producto_terminado_id]) }}" class="mt-3 space-y-2" onsubmit="return confirm('¿Confirmas el rechazo y retorno del producto a la etapa seleccionada?');">
                                                    @csrf
                                                    @method('PATCH')

                                                    <label class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Motivo del rechazo</label>
                                                    <select name="motivo_rechazo" required class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-rose-400">
                                                        <option value="">Selecciona un motivo</option>
                                                        @foreach (['Defecto de calidad', 'Dimensiones fuera de tolerancia', 'Acabado incompleto', 'Material incorrecto', 'Daño durante manipulación', 'Otro'] as $motivo)
                                                            <option value="{{ $motivo }}" @selected(old('motivo_rechazo') === $motivo)>{{ $motivo }}</option>
                                                        @endforeach
                                                    </select>

                                                    <label class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Etapa a la que regresará</label>
                                                    <select name="etapa_retorno" required class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-rose-400">
                                                        <option value="">Selecciona una etapa</option>
                                                        @foreach ($selectedRegistro->stepper_etapas as $etapaRetorno)
                                                            @php
                                                                $valorEtapaRetorno = $etapaRetorno->numero . '|' . $etapaRetorno->nombre;
                                                            @endphp
                                                            <option value="{{ $valorEtapaRetorno }}" @selected((string) old('etapa_retorno') === (string) $valorEtapaRetorno)>
                                                                {{ $etapaRetorno->numero }}. {{ $etapaRetorno->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <textarea name="observaciones_calidad" rows="2" placeholder="Detalle adicional del rechazo (opcional)" class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none focus:ring-2 focus:ring-rose-400">{{ old('decision') === 'RECHAZADO' ? old('observaciones_calidad') : '' }}</textarea>

                                                    <button type="submit" name="decision" value="RECHAZADO" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors">
                                                        Enviar rechazo
                                                    </button>
                                                </form>
                                            </details>
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-1.5 mt-2 text-[10px] font-bold text-slate-400 uppercase">
                                        <div class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[8px] text-slate-500">
                                            {{ substr($paso->responsable ?? 'S', 0, 1) }}
                                        </div>
                                        Responsable: {{ $paso->responsable ?? 'Sistema' }}
                                    </div>

                                    @if ($paso->aprobador)
                                        <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-green-700">
                                            Aprobado por: {{ $paso->aprobador }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-sm text-slate-400 italic py-4">
                                No hay movimientos registrados para esta orden.
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

