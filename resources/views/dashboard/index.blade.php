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
        $tablas = [
            ['titulo' => 'Ultimas entregas', 'data' => $ultimasEntregas, 'tipo' => 'entregas'],
            ['titulo' => 'Ultimas ordenes de produccion', 'data' => $ultimasOrdenes, 'tipo' => 'ordenes'],
        ];
    @endphp

    <div class="lc-page">
        <header class="lc-page-header">
            <div>
                <div class="lc-kicker">Centro de control</div>
                <h1 class="lc-title mt-2">Dashboard Operativo</h1>
                <p class="lc-subtitle mt-3">
                    Vista consolidada para la operacion diaria: pendientes criticos, accesos directos y actividad reciente del sistema.
                </p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Resumen</div>
                <div class="mt-1 font-semibold">Usa este tablero para detectar cuellos de botella antes de entrar a cada modulo.</div>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <article class="lc-stat-card flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-sky-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V4a2 2 0 1 0-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div>
                    <div class="lc-stat-label">Entregas pendientes</div>
                    <div class="lc-stat-value mt-1">{{ $kpis['entregasPendientesRevision'] }}</div>
                </div>
            </article>

            <article class="lc-stat-card flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z" />
                    </svg>
                </div>
                <div>
                    <div class="lc-stat-label">Insumos bajo minimo</div>
                    <div class="lc-stat-value mt-1">{{ $kpis['insumosBajoMinimo'] }}</div>
                </div>
            </article>

            <article class="lc-stat-card flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 0 1 8 0v2m-4-4a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-6 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <div class="lc-stat-label">Ordenes en proceso</div>
                    <div class="lc-stat-value mt-1">{{ $kpis['ordenesEnProceso'] }}</div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <article class="lc-card">
                <div class="lc-card-header">
                    <div>
                        <h2 class="lc-section-title">Ruta operativa recomendada</h2>
                        <p class="lc-section-subtitle">Secuencia sugerida para ejecutar el flujo sin perder contexto.</p>
                    </div>
                </div>
                <div class="lc-card-body">
                    <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-700">
                        <li><strong class="text-slate-900">Produccion:</strong> crear orden, validar stock y registrar consumo de materiales.</li>
                        <li><strong class="text-slate-900">Terminados:</strong> ingresar unidades finalizadas y ajustar stock auditado.</li>
                        <li><strong class="text-slate-900">Trazabilidad:</strong> revisar historial de lotes y movimientos por etapa.</li>
                        <li><strong class="text-slate-900">Reportes:</strong> filtrar por fechas y exportar indicadores en CSV.</li>
                    </ol>
                </div>
            </article>

            <article class="lc-card">
                <div class="lc-card-header">
                    <div>
                        <h2 class="lc-section-title">Accesos directos principales</h2>
                        <p class="lc-section-subtitle">Entradas rapidas para operar sin volver al menu.</p>
                    </div>
                </div>
                <div class="lc-card-body">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-3 text-sm font-semibold text-sky-900 transition hover:bg-sky-100" href="{{ route('insumos.index') }}">
                            1. Insumos
                        </a>
                        @if (($access['produccion'] ?? false))
                            <a class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-3 text-sm font-semibold text-sky-900 transition hover:bg-sky-100" href="{{ route('produccion.bom.index') }}">
                                2. Ordenes y recetas
                            </a>
                        @endif
                        <a class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-3 text-sm font-semibold text-sky-900 transition hover:bg-sky-100" href="{{ route('ordenes-compra.index') }}">
                            3. Compras
                        </a>
                        <a class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-3 text-sm font-semibold text-sky-900 transition hover:bg-sky-100" href="{{ route('entregas.index') }}">
                            4. Entregas
                        </a>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach ($tablas as $tabla)
                <article class="lc-card overflow-hidden">
                    <div class="lc-card-header">
                        <div>
                            <h2 class="lc-section-title">{{ $tabla['titulo'] }}</h2>
                            <p class="lc-section-subtitle">Ultima actividad registrada para seguimiento rapido.</p>
                        </div>
                    </div>
                    <div class="lc-table-wrap lc-scrollbar">
                        <table class="lc-table min-w-[560px]">
                            <thead>
                                <tr>
                                    @if ($tabla['tipo'] === 'entregas')
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Material</th>
                                        <th class="text-center">Revision</th>
                                    @else
                                        <th>Orden</th>
                                        <th>Producto</th>
                                        <th class="text-center">Estado</th>
                                        <th>Avance</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tabla['data'] as $item)
                                    <tr>
                                        @if ($tabla['tipo'] === 'entregas')
                                            <td>{{ optional($item->fecha_entrega)->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td class="font-medium text-slate-900">{{ $item->proveedor?->nombre ?? '-' }}</td>
                                            <td>{{ $item->material?->nombre ?? '-' }}</td>
                                            <td class="text-center"><span class="lc-badge lc-badge-neutral">{{ $item->estado_revision }}</span></td>
                                        @else
                                            <td class="font-mono text-xs font-bold text-sky-700">#{{ $item->id }}</td>
                                            <td class="font-medium text-slate-900">{{ $item->producto?->nombre ?? '-' }}</td>
                                            <td class="text-center"><span class="lc-badge lc-badge-neutral">{{ $item->estado?->nombre ?? '-' }}</span></td>
                                            <td class="font-mono text-xs italic">{{ number_format((float) $item->cantidad_completada, 2) }} / {{ number_format((float) $item->cantidad, 2) }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-sm italic text-slate-400">Sin registros recientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
@endsection
