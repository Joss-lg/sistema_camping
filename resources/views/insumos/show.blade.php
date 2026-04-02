@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Detalle de insumo</h1>
            <p class="text-slate-500 text-sm mt-1">Información general, inventario y estado actual del insumo.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('insumos.edit', $insumo) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar
            </a>
            <a href="{{ route('insumos.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
        <h2 class="text-lg font-bold text-slate-800 mb-6">Información del insumo</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Código</dt>
                <dd class="text-lg font-bold text-slate-900 mt-2 font-mono">{{ $insumo->codigo_insumo }}</dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Nombre</dt>
                <dd class="text-lg font-bold text-slate-900 mt-2">{{ $insumo->nombre }}</dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Estado</dt>
                <dd class="mt-2">
                    @if($insumo->estado === 'ACTIVO')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>ACTIVO
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-300 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>{{ $insumo->estado }}
                        </span>
                    @endif
                </dd>
            </div>
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200 rounded-2xl p-6">
                <dt class="text-emerald-700 text-xs font-semibold uppercase tracking-wider">Stock actual</dt>
                <dd class="text-3xl font-bold text-emerald-900 mt-2">{{ $insumo->stock_actual }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection
