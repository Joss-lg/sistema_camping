@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-slate-600">Panel SSR operativo para logística y producción.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <a href="{{ route('ordenes-produccion.index') }}" class="border rounded-lg p-4 bg-white hover:bg-slate-50">
            <h2 class="font-semibold">Órdenes de Producción</h2>
            <p class="text-sm text-slate-500">Gestionar ciclo de producción</p>
        </a>
        <a href="{{ route('insumos.index') }}" class="border rounded-lg p-4 bg-white hover:bg-slate-50">
            <h2 class="font-semibold">Insumos</h2>
            <p class="text-sm text-slate-500">Catálogo y stock</p>
        </a>
        <a href="{{ route('ordenes-compra.index') }}" class="border rounded-lg p-4 bg-white hover:bg-slate-50">
            <h2 class="font-semibold">Órdenes de Compra</h2>
            <p class="text-sm text-slate-500">Abastecimiento y recepción</p>
        </a>
        <a href="{{ route('trazabilidad.index') }}" class="border rounded-lg p-4 bg-white hover:bg-slate-50">
            <h2 class="font-semibold">Trazabilidad</h2>
            <p class="text-sm text-slate-500">Seguimiento por lote/serie</p>
        </a>
    </div>
</div>
@endsection
