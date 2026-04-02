@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-6" x-data="{ loading: false }">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Inventario de Terminados</h1>
            <p class="text-slate-500 text-sm mt-1">Gestiona el ingreso desde producción y ajustes de inventario final</p>
        </div>
        <span class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg text-xs font-bold border border-emerald-200">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6-4V4m0 6v4m4-10a2 2 0 110 4 2 2 0 010-4z"></path></svg>
            Flujo: Producción → Almacén
        </span>
    </div>

    {{-- Alertas --}}
    @include('partials.flash-messages')

    {{-- MÉtricas rápidas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Productos</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ $statsTotalProductos }}</p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM5 16a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-red-500">Stock Crítico</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ $statsStockBajo }}</p>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Lotes Activos</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ $statsLotes }}</p>
                </div>
                <div class="bg-slate-100 p-3 rounded-lg">
                    <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M7 9a2 2 0 11-4 0 2 2 0 014 0zm7-2a2 2 0 11-4 0 2 2 0 014 0zM5.3 13a3 3 0 015.4 0l3 3a2 2 0 01-2.828 2.828l-3.586-3.586a1 1 0 00-1.414 0l-3.586 3.586A2 2 0 01.3 16l3-3z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de inventario general --}}
    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Inventario general</h2>
                <p class="text-slate-500 text-sm mt-1">Estado actual del inventario de productos terminados.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[760px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="px-6 py-4 text-xs font-bold uppercase">Producto</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Almacén</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Identificación</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Categoría</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Unidad</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Estado</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-right">Stock</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Rangos</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-center">Trazabilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse ($productos as $producto)
                        @php $isLow = $producto->stock <= $producto->stock_minimo; @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors {{ $isLow ? 'bg-red-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800">{{ $producto->nombre }}</div>
                                <div class="text-[10px] font-mono text-slate-400 uppercase">{{ $producto->sku }}</div>
                            </td>
                            <td class="px-6 py-4 text-center text-xs">
                                <div class="font-semibold text-slate-700">{{ $producto->ubicacion->nombre ?? '-' }}</div>
                                <div class="text-slate-400">{{ $producto->ubicacion->codigo ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-[10px]">
                                <div class="font-semibold text-slate-700">Serie: {{ $producto->numero_serie ?? '-' }}</div>
                                <div class="text-slate-500">Bar: {{ $producto->codigo_barras ?? '-' }}</div>
                                <div class="text-slate-400 truncate max-w-[220px]">QR: {{ $producto->codigo_qr ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">{{ $producto->categoria?->nombre ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">{{ $producto->unidad?->nombre ?? '-' }}</td>
                            <td class="px-6 py-4 text-center text-[10px] font-bold uppercase">
                                <span class="px-2 py-1 rounded-full {{ $producto->estado_inventario === 'En Almacén' ? 'bg-emerald-100 text-emerald-700' : ($producto->estado_inventario === 'Pendiente Inspección' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    {{ $producto->estado_calidad === 'Pendiente Inspección' ? 'Pendiente inspección' : $producto->estado_inventario }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold {{ $isLow ? 'text-red-600' : 'text-slate-800' }}">{{ number_format((float) $producto->stock, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 items-center">
                                    <div class="w-full max-w-[110px] bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-blue-500 h-full" style="width: {{ min(($producto->stock / max($producto->stock_maximo, 1)) * 100, 100) }}%"></div>
                                    </div>
                                    <div class="flex justify-between w-full max-w-[110px] text-[9px] font-bold uppercase text-slate-400">
                                        <span>Min {{ number_format($producto->stock_minimo, 0) }}</span>
                                        <span>Max {{ number_format($producto->stock_maximo, 0) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(! empty($producto->numero_lote_produccion))
                                    <a href="{{ route('trazabilidad.show', $producto->numero_lote_produccion) }}" class="inline-flex items-center px-3 py-1 rounded-lg bg-sky-50 text-sky-700 text-xs font-bold border border-sky-100 hover:bg-sky-100">
                                        Ver lote
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Sin lote</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-slate-500">No hay productos terminados registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection