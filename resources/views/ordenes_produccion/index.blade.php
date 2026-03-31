@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Órdenes de Producción</h1>
            <p class="text-slate-500 text-sm mt-1">Gestiona, filtra y consulta el estado de cada orden.</p>
        </div>
        <a href="{{ route('ordenes-produccion.create') }}" class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2.5 rounded-lg shadow-sm transition-colors">
            Nueva orden
        </a>
    </div>

    <section class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar número o nota"
                class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">

            <select name="estado" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Estado</option>
                @foreach(\App\Models\OrdenProduccion::ESTADOS_FILTRO_UI as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>

            <select name="prioridad" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Prioridad</option>
                @foreach(['Alta', 'Media', 'Baja'] as $prioridad)
                    <option value="{{ $prioridad }}" @selected(request('prioridad') === $prioridad)>{{ $prioridad }}</option>
                @endforeach
            </select>

            <select name="tipo_producto_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Tipo de producto</option>
                @foreach($tiposProducto as $tipo)
                    <option value="{{ $tipo->id }}" @selected((string) request('tipo_producto_id') === (string) $tipo->id)>{{ $tipo->nombre }}</option>
                @endforeach
            </select>

            <button class="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 font-semibold text-sm transition-colors">Filtrar</button>
        </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[780px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">Número</th>
                        <th class="p-4 text-xs font-bold uppercase">Fecha</th>
                        <th class="p-4 text-xs font-bold uppercase">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase">Producto</th>
                        <th class="p-4 text-xs font-bold uppercase">Prioridad</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($ordenesProduccion as $orden)
                        @php
                            $estado = (string) ($orden->estado ?? 'Pendiente');
                            $estadoUi = \App\Models\OrdenProduccion::normalizarEstadoVisual($estado);
                            $badge = match ($estadoUi) {
                                'Finalizada' => 'bg-green-100 text-green-700',
                                'En Proceso' => 'bg-blue-100 text-blue-700',
                                'En Pausa' => 'bg-amber-100 text-amber-700',
                                'Cancelada' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-semibold">{{ $orden->numero_orden }}</td>
                            <td class="p-4">{{ $orden->fecha_orden }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $badge }}">{{ $estadoUi }}</span>
                            </td>
                            <td class="p-4">{{ $orden->tipoProducto?->nombre ?? '-' }}</td>
                            <td class="p-4">{{ $orden->prioridad ?? '-' }}</td>
                            <td class="p-4 text-center">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('ordenes-produccion.show', $orden) }}" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Ver</a>
                                    <a href="{{ route('ordenes-produccion.edit', $orden) }}" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Editar</a>
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
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $ordenesProduccion->links() }}
        </div>
    </section>
</div>
@endsection
