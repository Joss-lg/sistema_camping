@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start gap-[14px] mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard Operativo</h1>
            <p class="text-slate-500 mt-1.5 max-w-[780px]">
                Vista consolidada para la operación de productos de acampar, siguiendo la ruta definida en el plan de cierre.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5 mb-4">
        <article class="border border-slate-200 rounded-[10px] p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-[0.85rem]">Entregas pendientes de revisión</div>
            <div class="text-[1.45rem] font-bold text-slate-900 mt-1">{{ $kpis['entregasPendientesRevision'] }}</div>
        </article>

        <article class="border border-slate-200 rounded-[10px] p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-[0.85rem]">Insumos bajo mínimo</div>
            <div class="text-[1.45rem] font-bold text-slate-900 mt-1">{{ $kpis['insumosBajoMinimo'] }}</div>
        </article>

        <article class="border border-slate-200 rounded-[10px] p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-[0.85rem]">Órdenes en proceso</div>
            <div class="text-[1.45rem] font-bold text-slate-900 mt-1">{{ $kpis['ordenesEnProceso'] }}</div>
        </article>

        <article class="border border-slate-200 rounded-[10px] p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-[0.85rem]">Lotes generados hoy</div>
            <div class="text-[1.45rem] font-bold text-slate-900 mt-1">{{ $kpis['lotesGeneradosHoy'] }}</div>
        </article>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-[14px]">
        
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
            <h2 class="text-base font-bold text-slate-900 mb-2.5 border-b border-slate-50 pb-2">Accesos rápidos</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @if ($access['produccion'])
                    <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('produccion.index') }}">1. Producción</a>
                    <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('produccion.bom.index') }}">1.1 Ordenes y receta</a>
                @endif
                @if ($access['terminados'])
                    <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('terminados.index') }}">2. Terminados</a>
                @endif
                @if ($access['permisos'])
                    <a class="border border-blue-100 rounded-lg p-2.5 text-blue-900 bg-blue-50 font-semibold text-sm hover:bg-blue-100 transition-colors" href="{{ route('permisos.index') }}">Usuarios y permisos</a>
                @endif
            </div>
        </section>

        @php
            $tablas = [
                ['titulo' => 'Últimas entregas', 'data' => $ultimasEntregas, 'tipo' => 'entregas'],
                ['titulo' => 'Últimas órdenes de producción', 'data' => $ultimasOrdenes, 'tipo' => 'ordenes'],
                ['titulo' => 'Últimos lotes generados', 'data' => $ultimosLotes, 'tipo' => 'lotes']
            ];
        @endphp

        @foreach($tablas as $tabla)
        <section class="border border-slate-200 rounded-[10px] p-3.5 bg-white shadow-sm overflow-hidden">
            <h2 class="text-base font-bold text-slate-900 mb-2.5">{{ $tabla['titulo'] }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[500px]">
                    <thead>
                        <tr class="border-b border-slate-200">
                            @if($tabla['tipo'] == 'entregas')
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Fecha</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Proveedor</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Material</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold text-center">Revisión</th>
                            @elseif($tabla['tipo'] == 'ordenes')
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Orden</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Producto</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold text-center">Estado</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Avance</th>
                            @else
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Lote</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Producto</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold text-center">Estado</th>
                                <th class="py-2 px-2 text-slate-500 text-[0.78rem] uppercase tracking-wider font-semibold">Fecha</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tabla['data'] as $item)
                            <tr class="text-[0.88rem] text-slate-700 hover:bg-slate-50 transition-colors">
                                @if($tabla['tipo'] == 'entregas')
                                    <td class="py-2.5 px-2">{{ optional($item->fecha_entrega)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->proveedor?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2">{{ $item->material?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-[0.75rem] font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado_revision }}</span></td>
                                @elseif($tabla['tipo'] == 'ordenes')
                                    <td class="py-2.5 px-2 font-mono text-xs text-blue-600 font-bold">#{{ $item->id }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->producto?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-[0.75rem] font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado?->nombre ?? '-' }}</span></td>
                                    <td class="py-2.5 px-2 font-mono text-xs italic">{{ number_format((float)$item->cantidad_completada, 2) }} / {{ number_format((float)$item->cantidad, 2) }}</td>
                                @else
                                    <td class="py-2.5 px-2 font-mono text-xs font-bold">{{ $item->numero_lote }}</td>
                                    <td class="py-2.5 px-2 font-medium text-slate-900">{{ $item->producto?->nombre ?? '-' }}</td>
                                    <td class="py-2.5 px-2 text-center"><span class="inline-block rounded-full px-2 py-0.5 text-[0.75rem] font-bold bg-slate-100 text-slate-900 border border-slate-200">{{ $item->estado?->nombre ?? '-' }}</span></td>
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