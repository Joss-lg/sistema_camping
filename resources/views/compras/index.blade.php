@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Órdenes de Compra</h1>
            <p class="text-slate-500 text-sm mt-1">Seguimiento de compras por proveedor, estado y monto.</p>
        </div>
        <a href="{{ route('ordenes-compra.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-bold shadow-sm">Nueva</a>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Número de orden" class="border border-slate-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-green-500 outline-none">
            <select name="proveedor_id" class="border border-slate-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Proveedor</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((string) request('proveedor_id') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                @endforeach
            </select>
            <select name="estado" class="border border-slate-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Estado</option>
                @foreach(['Pendiente','Confirmada','Recibida','Cancelada'] as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            <button class="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 font-semibold text-sm">Filtrar</button>
        </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">Número</th>
                        <th class="p-4 text-xs font-bold uppercase">Proveedor</th>
                        <th class="p-4 text-xs font-bold uppercase">Fecha</th>
                        <th class="p-4 text-xs font-bold uppercase">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase">Total</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($ordenesCompra as $ordenCompra)
                        @php
                            $estado = (string) ($ordenCompra->estado ?? '-');
                            $badge = match ($estado) {
                                'Recibida' => 'bg-green-100 text-green-700',
                                'Confirmada' => 'bg-blue-100 text-blue-700',
                                'Cancelada' => 'bg-red-100 text-red-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-semibold">{{ $ordenCompra->numero_orden }}</td>
                            <td class="p-4">{{ $ordenCompra->proveedor?->razon_social ?? '-' }}</td>
                            <td class="p-4">{{ $ordenCompra->fecha_orden }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $badge }}">{{ $estado }}</span>
                            </td>
                            <td class="p-4">{{ number_format((float) $ordenCompra->monto_total, 2) }}</td>
                            <td class="p-4 text-center">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('ordenes-compra.show', $ordenCompra) }}" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Ver</a>
                                    <a href="{{ route('ordenes-compra.edit', $ordenCompra) }}" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-6 text-center text-slate-500" colspan="6">Sin registros</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">{{ $ordenesCompra->links() }}</div>
    </section>
</div>
@endsection
