@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Detalle de orden de compra</h1>
            <p class="text-slate-500 text-sm mt-1">Vista de resumen de la orden y su proveedor asociado.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ordenes-compra.edit', $ordenCompra) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-bold">Editar</a>
            <a href="{{ route('ordenes-compra.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold">Volver</a>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Numero</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $ordenCompra->numero_orden }}</dd>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Estado</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $ordenCompra->estado }}</dd>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <dt class="text-slate-500 font-semibold">Proveedor</dt>
                <dd class="text-slate-800 font-bold mt-1">{{ $ordenCompra->proveedor?->razon_social ?? '-' }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection
