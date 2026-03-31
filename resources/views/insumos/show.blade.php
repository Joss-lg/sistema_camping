@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Detalle de insumo</h1>
            <p class="text-slate-500 text-sm mt-1">Informacion general y estado de inventario.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('insumos.edit', $insumo) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-bold">Editar</a>
            <a href="{{ route('insumos.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold">Volver</a>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Codigo</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $insumo->codigo_insumo }}</dd>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Nombre</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $insumo->nombre }}</dd>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Estado</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $insumo->estado }}</dd>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Stock actual</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $insumo->stock_actual }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection
