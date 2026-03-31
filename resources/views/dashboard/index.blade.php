@extends('layouts.app')

@section('content')
    @php
        $kpis = $kpis ?? [
            'entregasPendientesRevision' => 0,
            'insumosBajoMinimo' => 0,
            'ordenesEnProceso' => 0,
        ];
        $access = $access ?? ['produccion' => false];
        $ultimasEntregas = $ultimasEntregas ?? collect();
        $ultimasOrdenes = $ultimasOrdenes ?? collect();
    @endphp

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard Operativo</h1>
            <p class="text-slate-500 mt-2 max-w-[780px] text-base">
                Vista consolidada para la operación de productos de acampar, siguiendo la ruta definida en el plan de cierre.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        <article class="flex items-center gap-3 border border-slate-200 rounded-xl p-4 bg-gradient-to-br from-white to-slate-50 shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600">
                <svg xmlns=\"http://www.w3.org/2000/svg\" class=\"w-7 h-7\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9\" /></svg>
            </div>
            <div>
                <div class="text-slate-500 text-xs font-semibold uppercase">Entregas pendientes de revisión</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $kpis['entregasPendientesRevision'] }}</div>
            </div>
        </article>
        <article class="flex items-center gap-3 border border-slate-200 rounded-xl p-4 bg-gradient-to-br from-white to-slate-50 shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 text-yellow-600">
                <svg xmlns=\"http://www.w3.org/2000/svg\" class=\"w-7 h-7\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z\" /></svg>
            </div>
            <div>
                <div class="text-slate-500 text-xs font-semibold uppercase">Insumos bajo mínimo</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $kpis['insumosBajoMinimo'] }}</div>
            </div>
        </article>
        <article class="flex items-center gap-3 border border-slate-200 rounded-xl p-4 bg-gradient-to-br from-white to-slate-50 shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-600">
                <svg xmlns=\"http://www.w3.org/2000/svg\" class=\"w-7 h-7\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 17v-2a4 4 0 018 0v2m-4-4a4 4 0 100-8 4 4 0 000 8zm-6 8a2 2 0 012-2h12a2 2 0 012 2v1H3v-1z\" /></svg>
            </div>
            <div>
                <div class="text-slate-500 text-xs font-semibold uppercase">Órdenes en proceso</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $kpis['ordenesEnProceso'] }}</div>
            </div>
        </article>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <section class="border border-slate-200 rounded-[10px] p-3.5 bg-white shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-2.5 border-b border-slate-50 pb-2">Ruta operativa recomendada</h2>
            <ol class="list-decimal pl-[18px] space-y-2 text-slate-700 text-sm">
                <li><strong class="text-slate-900">Producción:</strong> crear orden, validar stock y registrar consumo de materiales.</li>
                <li><strong class="text-slate-900">Terminados:</strong> ingresar unidades finalizadas y ajustar stock auditado.</li>
                <li><strong class="text-slate-900">Trazabilidad:</strong> revisar historial de lotes y movimientos por etapa.</li>
                <li><strong class="text-slate-900">Reportes:</strong> filtrar por fechas y exportar indicadores en CSV.</li>
            </ol>
        </section>

<section class="border border-slate-200 rounded-[10px] p-3.5 bg-white shadow-sm">
    <h2 class="text-base font-bold text-slate-900 mb-2.5 border-b border-slate-50 pb-2">Accesos directos principales</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('insumos.index') }}">
            1. Insumos
        </a>
        @if (($access['produccion'] ?? false))
            <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('produccion.bom.index') }}">
                2. Órdenes y recetas
            </a>
        @endif
            <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('ordenes-compra.index') }}">
            3. Compras
        </a>
        <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('entregas.index') }}">
            4. Entregas
        </a>
    </div>
</section>
        @php
            $tablas = [
                ['titulo' => 'Últimas entregas', 'data' => $ultimasEntregas, 'tipo' => 'entregas'],
                ['titulo' => 'Últimas órdenes de producción', 'data' => $ultimasOrdenes, 'tipo' => 'ordenes'],
                
            ];
        @endphp

        @foreach($tablas as $tabla)
        <section class="border border-slate-200 rounded-[10px] p-4 bg-white shadow-md mt-6 overflow-hidden">
            <h2 class="text-base font-bold text-slate-900 mb-3">{{ $tabla['titulo'] }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[500px]">
                    <thead class="bg-slate-50 sticky top-0 z-10">
                        <tr class="border-b border-slate-200">
                            @if($tabla['tipo'] == 'entregas')
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Fecha</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Proveedor</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Material</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold text-center">Revisión</th>
                            @elseif($tabla['tipo'] == 'ordenes')
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Orden</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Producto</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold text-center">Estado</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Avance</th>
                            @else
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Lote</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Producto</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold text-center">Estado</th>
                                <th class="py-2 px-2 text-slate-500 text-xs uppercase tracking-wider font-semibold">Fecha</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tabla['data'] as $item)
                            <tr class="text-[0.95rem] text-slate-700 hover:bg-blue-50/60 transition-colors">
                                @if($tabla['tipo'] == 'entregas')
                                    <td class="py-2.5 px-2">{{ optional($item->fecha_entrega)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->proveedor?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2">{{ $item->material?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-xs font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado_revision }}</span></td>
                                @elseif($tabla['tipo'] == 'ordenes')
                                    <td class="py-2.5 px-2 font-mono text-xs text-blue-600 font-bold">#{{ $item->id }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->producto?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-xs font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado?->nombre ?? '-' }}</span></td>
                                    <td class="py-2.5 px-2 font-mono text-xs italic">{{ number_format((float)$item->cantidad_completada, 2) }} / {{ number_format((float)$item->cantidad, 2) }}</td>
                                @else
                                    <td class="py-2.5 px-2 font-mono text-xs font-bold">{{ $item->numero_lote }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->producto?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-xs font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado?->nombre ?? '-' }}</span></td>
                                    <td class="py-2.5 px-2">{{ optional($item->fecha_produccion)->format('Y-m-d H:i') ?? '-' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 px-2 text-center text-slate-400 italic">Sin registros recientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endforeach
    </div>
@endsection