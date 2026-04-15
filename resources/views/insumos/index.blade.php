@extends('layouts.app')

@section('content')
<div class="lc-page">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Inventario base</div>
            <h1 class="lc-title">Insumos</h1>
            <p class="lc-subtitle">Controla inventario, criticidad de stock y trazabilidad de abastecimiento en una sola vista operativa.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="lc-badge lc-badge-neutral">{{ method_exists($insumos, 'total') ? $insumos->total() : $insumos->count() }} registros</span>
            <a href="{{ route('insumos.create') }}" class="lc-btn-primary">Nuevo insumo</a>
        </div>
    </section>

    <section class="lc-toolbar" x-data="{ loading: false }">
        <div>
            <h2 class="lc-section-title">Filtros de inventario</h2>
            <p class="lc-section-subtitle">Busca por código, categoría, proveedor y estado para identificar faltantes rápidamente.</p>
        </div>
        <form method="GET" class="lc-toolbar-form w-full xl:max-w-5xl" x-on:submit="loading = true">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar código o nombre" class="lc-input">
            <select name="categoria_insumo_id" class="lc-select">
                <option value="">Categoría</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria_insumo_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <select name="proveedor_id" class="lc-select">
                <option value="">Proveedor</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((string) request('proveedor_id') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                @endforeach
            </select>
            <select name="estado" class="lc-select">
                <option value="">Estado</option>
                @foreach(['Activo', 'Inactivo', 'Agotado'] as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            <div class="flex gap-3">
                <button type="submit" class="lc-btn-secondary flex-1" x-bind:disabled="loading" x-bind:aria-busy="loading.toString()">
                    <svg x-cloak x-show="loading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="loading ? 'Filtrando...' : 'Filtrar'"></span>
                </button>
                @if(request()->filled('q') || request()->filled('categoria_insumo_id') || request()->filled('proveedor_id') || request()->filled('estado'))
                    <a href="{{ route('insumos.index') }}" class="lc-btn-secondary">Limpiar</a>
                @endif
            </div>
        </form>
    </section>

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Listado de insumos</h2>
                <p class="lc-section-subtitle">Prioriza material con riesgo de quiebre y acceso rápido a edición.</p>
            </div>
        </div>
        <div class="lc-table-wrap lc-scrollbar">
            <table class="lc-table min-w-[880px]">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría / Proveedor</th>
                        <th>Stock</th>
                        <th>Reabastecimiento</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($insumos as $insumo)
                        @php
                            $estado = ! $insumo->activo
                                ? 'Inactivo'
                                : (string) ($insumo->estado ?? '-');
                            $badge = match ($estado) {
                                'Activo' => 'lc-badge lc-badge-success',
                                'Agotado' => 'lc-badge border-red-200 bg-red-50 text-red-700',
                                'Inactivo' => 'lc-badge lc-badge-neutral',
                                default => 'lc-badge lc-badge-neutral',
                            };
                            $stockEntrante = (float) ($insumo->stock_entrante_confirmado ?? 0);
                            $stockReservado = (float) ($insumo->stock_reservado ?? 0);
                            $stockDisponible = max(0, (float) $insumo->stock_actual - $stockReservado);
                            $stockProyectado = $stockDisponible + $stockEntrante;
                            $estaBajo = $stockProyectado <= (float) $insumo->stock_minimo;
                            $faltante = max(0, (float) $insumo->stock_minimo - (float) $insumo->stock_actual);
                            $cantidadSugerida = max((float) ($insumo->cantidad_minima_orden ?? 0), $faltante > 0 ? $faltante : 1);
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $insumo->codigo_insumo }}</div>
                                <div class="text-xs text-slate-400">Unidad base: {{ $insumo->unidadMedida?->nombre ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $insumo->nombre }}</div>
                                <div class="text-xs text-slate-500">{{ $insumo->descripcion_corta ?? 'Sin descripción corta' }}</div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-700">{{ $insumo->categoriaInsumo?->nombre ?? 'Sin categoría' }}</div>
                                <div class="text-xs text-slate-500">{{ $insumo->proveedor?->nombre_comercial ?: $insumo->proveedor?->razon_social ?: 'Sin proveedor' }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ number_format($stockProyectado, 2) }}</div>
                                <div class="text-xs text-slate-500">Actual: {{ number_format((float) $insumo->stock_actual, 2) }} | Mínimo: {{ number_format((float) $insumo->stock_minimo, 2) }}</div>
                                @if($stockReservado > 0)
                                    <div class="mt-1 text-xs text-amber-700">Reservado: {{ number_format($stockReservado, 2) }} | Disponible: {{ number_format($stockDisponible, 2) }}</div>
                                @endif
                                @if($stockEntrante > 0)
                                    <div class="mt-1 text-xs text-emerald-700">Entrante confirmado: +{{ number_format($stockEntrante, 2) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($estaBajo)
                                    <span class="lc-badge lc-badge-warning">Stock bajo</span>
                                    <a
                                        href="{{ route('ordenes-compra.create', ['reabastecer_insumo_id' => $insumo->id, 'cantidad_sugerida' => $cantidadSugerida]) }}"
                                        class="mt-2 inline-flex items-center rounded-md bg-amber-500 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-amber-600"
                                    >
                                        Solicitar reabastecimiento
                                    </a>
                                @else
                                    <span class="lc-badge lc-badge-success">Stock estable</span>
                                @endif
                                <div class="mt-2 text-xs text-slate-500">Mín. orden: {{ number_format((float) $insumo->cantidad_minima_orden, 2) }}</div>
                            </td>
                            <td>
                                <span class="{{ $badge }}">{{ $estado }}</span>
                            </td>
                            <td>
                                <div class="lc-table-actions">
                                    <a href="{{ route('insumos.show', $insumo) }}" class="lc-icon-btn lc-icon-btn-info" title="Ver insumo" aria-label="Ver insumo {{ $insumo->nombre }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('insumos.edit', $insumo) }}" class="lc-icon-btn lc-icon-btn-warning" title="Editar insumo" aria-label="Editar insumo {{ $insumo->nombre }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="lc-empty-state my-4">
                                    <div class="lc-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v10.125a2.625 2.625 0 0 1-2.625 2.625H6.375A2.625 2.625 0 0 1 3.75 17.625V6.375A2.625 2.625 0 0 1 6.375 3.75H13.5l6.75 3.75Z" />
                                        </svg>
                                    </div>
                                    <div class="lc-empty-title">No hay insumos para mostrar</div>
                                    <p class="lc-empty-copy">Ajusta los filtros o registra un nuevo insumo para empezar a alimentar el inventario operativo.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lc-pagination-shell">{{ $insumos->links() }}</div>
    </section>
</div>
@endsection
