@extends('layouts.app')

@section('content')
<div class="lc-page">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Órdenes de compra</h1>
            <p class="lc-subtitle">Monitorea proveedor, fecha compromiso, estado y monto para tomar decisiones de abastecimiento sin salir del tablero.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="lc-badge lc-badge-neutral">{{ method_exists($ordenesCompra, 'total') ? $ordenesCompra->total() : $ordenesCompra->count() }} órdenes</span>
        </div>
    </section>

    <section class="lc-toolbar" x-data="{ loading: false }">
        <div>
            <h2 class="lc-section-title">Filtros de compra</h2>
            <p class="lc-section-subtitle">Encuentra órdenes por número, proveedor o estado para validar recepción y atrasos.</p>
        </div>
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4 w-full xl:max-w-4xl" x-on:submit="loading = true">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Número de orden" class="lc-input">
            <select name="proveedor_id" class="lc-select">
                <option value="">Proveedor</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((string) request('proveedor_id') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                @endforeach
            </select>
            <select name="estado" class="lc-select">
                <option value="">Estado</option>
                @foreach(['Pendiente','Confirmada','Recibida','Cancelada'] as $estado)
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
                @if(request()->filled('q') || request()->filled('proveedor_id') || request()->filled('estado'))
                    <a href="{{ route('ordenes-compra.index') }}" class="lc-btn-secondary">Limpiar</a>
                @endif
            </div>
        </form>
    </section>

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Seguimiento de órdenes</h2>
                <p class="lc-section-subtitle">Visualiza el compromiso financiero y el avance de recepción con acceso directo a detalle y edición.</p>
            </div>
        </div>
        <div class="lc-table-wrap lc-scrollbar">
            <table class="lc-table min-w-[900px]">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenesCompra as $ordenCompra)
                        @php
                            $estado = (string) ($ordenCompra->estado ?? '-');
                            $badge = match ($estado) {
                                'Recibida' => 'lc-badge lc-badge-success',
                                'Confirmada' => 'lc-badge border-sky-200 bg-sky-50 text-sky-700',
                                'Cancelada' => 'lc-badge border-red-200 bg-red-50 text-red-700',
                                default => 'lc-badge lc-badge-warning',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $ordenCompra->numero_orden }}</div>
                                <div class="text-xs text-slate-500">{{ $ordenCompra->condiciones_pago ?: 'Sin condición registrada' }}</div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-800">{{ $ordenCompra->proveedor?->razon_social ?? '-' }}</div>
                                <div class="text-xs text-slate-500">Entrega prevista: {{ optional($ordenCompra->fecha_entrega_prevista)->format('d/m/Y') ?? 'N/A' }}</div>
                            </td>
                            <td>{{ optional($ordenCompra->fecha_orden)->format('d/m/Y') ?? $ordenCompra->fecha_orden }}</td>
                            <td><span class="{{ $badge }}">{{ $estado }}</span></td>
                            <td>
                                <div class="font-semibold text-slate-900">${{ number_format((float) $ordenCompra->monto_total, 2) }}</div>
                                <div class="text-xs text-slate-500">Imp.: ${{ number_format((float) $ordenCompra->impuestos, 2) }}</div>
                            </td>
                            <td>
                                <div class="lc-table-actions">
                                    <a href="{{ route('ordenes-compra.show', $ordenCompra) }}" class="lc-icon-btn lc-icon-btn-info" title="Ver orden" aria-label="Ver orden {{ $ordenCompra->numero_orden }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('ordenes-compra.edit', $ordenCompra) }}" class="lc-icon-btn lc-icon-btn-warning" title="Editar orden" aria-label="Editar orden {{ $ordenCompra->numero_orden }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="lc-empty-state my-4">
                                    <div class="lc-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a.75.75 0 0 1 .727.568l.651 2.605m0 0 1.54 6.161a2.25 2.25 0 0 0 2.183 1.703h7.632a2.25 2.25 0 0 0 2.183-1.703l1.154-4.616a.75.75 0 0 0-.727-.932H5.014Z" />
                                        </svg>
                                    </div>
                                    <div class="lc-empty-title">No hay órdenes de compra registradas</div>
                                    <p class="lc-empty-copy">Genera una orden nueva o cambia los filtros para revisar otro subconjunto de abastecimiento.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lc-pagination-shell">{{ $ordenesCompra->links() }}</div>
    </section>
</div>
@endsection
