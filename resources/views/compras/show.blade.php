@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Detalle de orden de compra</h1>
            <p class="text-slate-500 text-sm mt-1">Resumen de la orden #{{ $ordenCompra->id }} y proveedor asociado.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ordenes-compra.edit', $ordenCompra) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar
            </a>
            <a href="{{ route('ordenes-compra.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
        <h2 class="text-lg font-bold text-slate-800 mb-6">Información general</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Número de orden</dt>
                <dd class="text-2xl font-bold text-slate-900 mt-2">#{{ $ordenCompra->numero_orden }}</dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Estado</dt>
                <dd class="mt-2">
                    @if($ordenCompra->estado === 'PENDIENTE')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-amber-600"></span>PENDIENTE
                        </span>
                    @elseif($ordenCompra->estado === 'CONFIRMADA')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>CONFIRMADA
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>{{ $ordenCompra->estado }}
                        </span>
                    @endif
                </dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Proveedor asignado</dt>
                <dd class="text-lg font-semibold text-slate-900 mt-2">{{ $ordenCompra->proveedor?->razon_social ?? 'Sin asignar' }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection
