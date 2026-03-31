@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Insumos</h1>
            <p class="text-slate-500 text-sm mt-1">Control de inventario, filtros y consulta de estado.</p>
        </div>
        <a href="{{ route('insumos.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-bold shadow-sm">Nuevo</a>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar código o nombre" class="border border-slate-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
            <select name="categoria_insumo_id" class="border border-slate-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Categoría</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria_insumo_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <select name="proveedor_id" class="border border-slate-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Proveedor</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((string) request('proveedor_id') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                @endforeach
            </select>
            <select name="estado" class="border border-slate-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Estado</option>
                @foreach(['Activo', 'Inactivo', 'Agotado'] as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            <button class="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 font-semibold text-sm">Filtrar</button>
        </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[760px] text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">Código</th>
                        <th class="p-4 text-xs font-bold uppercase">Nombre</th>
                        <th class="p-4 text-xs font-bold uppercase">Categoría / Proveedor</th>
                        <th class="p-4 text-xs font-bold uppercase">Stock</th>
                        <th class="p-4 text-xs font-bold uppercase">Reabastecimiento</th>
                        <th class="p-4 text-xs font-bold uppercase">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($insumos as $insumo)
                        @php
                            $estado = (string) ($insumo->estado ?? '-');
                            $badge = match ($estado) {
                                'Activo' => 'bg-green-100 text-green-700',
                                'Agotado' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                            $estaBajo = (float) $insumo->stock_actual <= (float) $insumo->stock_minimo;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-semibold">{{ $insumo->codigo_insumo }}</td>
                            <td class="p-4">{{ $insumo->nombre }}</td>
                            <td class="p-4">
                                <div class="font-medium text-slate-700">{{ $insumo->categoriaInsumo?->nombre ?? 'Sin categoría' }}</div>
                                <div class="text-xs text-slate-500">{{ $insumo->proveedor?->nombre_comercial ?: $insumo->proveedor?->razon_social ?: 'Sin proveedor' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-semibold">{{ $insumo->stock_actual }}</div>
                                <div class="text-xs text-slate-500">Mínimo: {{ $insumo->stock_minimo }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $estaBajo ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $estaBajo ? 'Solicitar compra' : 'Stock estable' }}
                                </span>
                                <div class="text-xs text-slate-500 mt-1">Mín. orden: {{ $insumo->cantidad_minima_orden }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $badge }}">{{ $estado }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('insumos.show', $insumo) }}" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Ver</a>
                                    <a href="{{ route('insumos.edit', $insumo) }}" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-6 text-center text-slate-500" colspan="7">Sin registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">{{ $insumos->links() }}</div>
    </section>
</div>
@endsection
