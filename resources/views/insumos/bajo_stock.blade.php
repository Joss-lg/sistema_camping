@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Insumos bajo stock</h1>
            <p class="text-slate-500 text-sm mt-1">Listado de insumos en nivel critico para reposicion.</p>
        </div>
        <a href="{{ route('insumos.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold">Volver</a>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left min-w-[620px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">Codigo</th>
                        <th class="p-4 text-xs font-bold uppercase">Nombre</th>
                        <th class="p-4 text-xs font-bold uppercase">Stock</th>
                        <th class="p-4 text-xs font-bold uppercase">Minimo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($insumos as $insumo)
                        <tr class="hover:bg-red-50/40 transition-colors">
                            <td class="p-4 font-semibold">{{ $insumo->codigo_insumo }}</td>
                            <td class="p-4">{{ $insumo->nombre }}</td>
                            <td class="p-4 text-red-700 font-bold">{{ $insumo->stock_actual }}</td>
                            <td class="p-4">{{ $insumo->stock_minimo }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-6 text-center text-slate-500" colspan="4">Sin insumos criticos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100">{{ $insumos->links() }}</div>
    </section>
</div>
@endsection
