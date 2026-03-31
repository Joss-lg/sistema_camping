@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Terminados</h1>
            <p class="text-slate-500 text-sm mt-1">Registra ingresos desde producción y aplica ajustes auditados sobre inventario final.</p>
        </div>
        <span class="inline-flex items-center bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
            Flujo: Producción a Almacén
        </span>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 items-end">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-600">Filtrar por almacén</label>
                <select name="ubicacion_almacen_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-slate-500 outline-none">
                    <option value="">Todas las ubicaciones</option>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected((string) $ubicacionFiltro === (string) $ubicacion->id)>
                            {{ $ubicacion->nombre }} ({{ $ubicacion->codigo_ubicacion }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 font-semibold text-sm">Filtrar</button>
            <a href="{{ route('terminados.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg px-4 py-2.5 font-semibold text-sm text-center">Limpiar</a>
        </form>
    </section>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Productos</div>
            <div class="text-3xl font-extrabold text-slate-800 mt-2">{{ $statsTotalProductos }}</div>
        </article>
        <article class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold uppercase text-red-500">Stock Crítico</div>
            <div class="text-3xl font-extrabold text-slate-800 mt-2">{{ $statsStockBajo }}</div>
        </article>
        <article class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Lotes Activos</div>
            <div class="text-3xl font-extrabold text-slate-800 mt-2">{{ $statsLotes }}</div>
        </article>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Ingreso desde producción</h2>
                    <p class="text-slate-500 text-sm mt-1">Convierte una orden finalizada en inventario disponible.</p>
                </div>
                <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-100 rounded-full px-3 py-1">
                    {{ $ordenesFinalizadas->count() }} órdenes
                </span>
            </div>

            <form method="POST" action="{{ route('terminados.ingresos.store') }}" class="space-y-4">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Orden finalizada</label>
                    <select name="orden_produccion_id" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Selecciona una orden</option>
                        @foreach ($ordenesFinalizadas as $orden)
                            @php $pendiente = max((float) $orden->cantidad_completada - (float) ($orden->cantidad_ingresada ?? 0), 0); @endphp
                            <option value="{{ $orden->id }}" @selected((string) old('orden_produccion_id') === (string) $orden->id)>
                                #{{ $orden->id }} - {{ $orden->producto?->nombre }} (Pendiente: {{ number_format($pendiente, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Cantidad de ingreso</label>
                    <input name="cantidad_ingreso" type="number" step="0.01" value="{{ old('cantidad_ingreso') }}" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Ubicación destino</label>
                    <select name="ubicacion_almacen_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">Ubicación activa por defecto</option>
                        @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}" @selected((string) old('ubicacion_almacen_id') === (string) $ubicacion->id)>
                                {{ $ubicacion->nombre }} ({{ $ubicacion->codigo_ubicacion }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg shadow-sm">Registrar entrada</button>
            </form>
        </section>

        <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-bold text-slate-800">Ajuste de inventario</h2>
                <p class="text-slate-500 text-sm mt-1">Aplica un movimiento manual auditado sobre producto terminado.</p>
            </div>

            <form method="POST" action="{{ route('terminados.ajustes.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div class="md:col-span-2 flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Producto</label>
                    <select name="producto_id" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Selecciona producto</option>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}" @selected((string) old('producto_id') === (string) $producto->id)>
                                {{ $producto->nombre }} (Stock: {{ number_format($producto->stock, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Tipo de ajuste</label>
                    <select name="tipo_ajuste" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="SUMAR" @selected(old('tipo_ajuste') === 'SUMAR')>Sumar</option>
                        <option value="RESTAR" @selected(old('tipo_ajuste') === 'RESTAR')>Restar</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Cantidad</label>
                    <input name="cantidad" type="number" step="0.01" value="{{ old('cantidad') }}" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="md:col-span-2 flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Motivo</label>
                    <textarea name="motivo" rows="3" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">{{ old('motivo') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg shadow-sm">Aplicar ajuste</button>
                </div>
            </form>
        </section>
    </div>

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
                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">No hay productos terminados registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection