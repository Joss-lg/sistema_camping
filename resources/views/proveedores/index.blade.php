@extends('layouts.app')

@section('content')
<div class="lc-page">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Proveedores</h1>
            <p class="lc-subtitle">Centraliza contactos, condiciones comerciales y estado de relación para sostener el flujo de compras sin fricción.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="lc-badge lc-badge-neutral">{{ method_exists($proveedores, 'total') ? $proveedores->total() : $proveedores->count() }} proveedores</span>
            <a href="{{ route('proveedores.create') }}" class="lc-btn-primary">Nuevo proveedor</a>
        </div>
    </section>

    <section class="lc-toolbar" x-data="{ loading: false }">
        <div>
            <h2 class="lc-section-title">Búsqueda rápida</h2>
            <p class="lc-section-subtitle">Encuentra proveedores por nombre, RFC, tipo, contacto o correo para actuar rápido sobre abastecimiento.</p>
        </div>
        <form method="GET" action="{{ route('proveedores.index') }}" class="flex w-full flex-col gap-3 lg:max-w-3xl lg:flex-row" x-on:submit="loading = true">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.4a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z" />
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre, RFC, tipo, contacto o correo" class="lc-input pl-10">
            </div>
            <button type="submit" class="lc-btn-secondary min-w-[140px]" x-bind:disabled="loading" x-bind:aria-busy="loading.toString()">
                <svg x-cloak x-show="loading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                </svg>
                <span x-text="loading ? 'Buscando...' : 'Buscar'"></span>
            </button>
            @if($q)
                <a href="{{ route('proveedores.index') }}" class="lc-btn-secondary">Limpiar</a>
            @endif
        </form>
    </section>

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Directorio operativo</h2>
                <p class="lc-section-subtitle">Consulta abastecimiento, tiempos de entrega y estado de cada proveedor desde una tabla unificada.</p>
            </div>
        </div>
        <div class="lc-table-wrap lc-scrollbar">
            <table class="lc-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre / Empresa</th>
                        <th>Contacto</th>
                        <th>Comunicación</th>
                        <th>Abastecimiento</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proveedores as $proveedor)
                        @php
                            $estadoNombre = strtoupper((string) ($proveedor->estado->nombre ?? 'INACTIVO'));
                            $esActivo = $estadoNombre === 'ACTIVO';
                        @endphp
                        <tr>
                            <td>
                                <div class="font-mono text-xs font-semibold text-slate-500">#{{ $proveedor->id }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $proveedor->nombre }}</div>
                                <div class="text-xs text-slate-500">RFC: {{ $proveedor->rfc ?: 'Sin RFC' }}</div>
                                <div class="text-xs text-slate-500">Tipo: {{ $proveedor->tipo_proveedor ?: 'Sin tipo' }}</div>
                                <div class="text-xs text-slate-500">{{ $proveedor->condiciones_pago ?: 'Sin condición de pago registrada' }}</div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-700">{{ $proveedor->contacto ?: 'Sin asignar' }}</div>
                                <div class="text-xs text-slate-500">Calidad: {{ number_format((float) $proveedor->calificacion, 1) }}/5</div>
                            </td>
                            <td>
                                <div class="space-y-1 text-sm text-slate-600">
                                    <div>{{ $proveedor->email ?: 'Correo no registrado' }}</div>
                                    <div class="text-xs text-slate-500">{{ $proveedor->telefono ?: 'Teléfono no registrado' }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="space-y-1 text-sm text-slate-600">
                                    <div>Entrega: <span class="font-semibold text-slate-800">{{ $proveedor->tiempo_entrega_dias }} días</span></div>
                                    <div>Crédito: <span class="font-semibold text-slate-800">{{ $proveedor->dias_credito }} días</span></div>
                                    <div>Límite: <span class="font-semibold text-slate-800">${{ number_format((float) $proveedor->limite_credito, 2) }}</span></div>
                                </div>
                            </td>
                            <td>
                                <span class="{{ $esActivo ? 'lc-badge lc-badge-success' : 'lc-badge lc-badge-neutral' }}">{{ $proveedor->estado->nombre ?? 'Inactivo' }}</span>
                            </td>
                            <td>
                                <div class="lc-table-actions">
                                    <a href="{{ route('proveedores.edit', $proveedor->id) }}" class="lc-icon-btn lc-icon-btn-warning" title="Editar proveedor" aria-label="Editar proveedor {{ $proveedor->nombre }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('proveedores.toggle-estado', $proveedor->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="lc-icon-btn {{ $esActivo ? 'lc-icon-btn-danger' : 'lc-icon-btn-info' }}" title="{{ $esActivo ? 'Desactivar proveedor' : 'Activar proveedor' }}" aria-label="{{ $esActivo ? 'Desactivar' : 'Activar' }} {{ $proveedor->nombre }}">
                                            @if($esActivo)
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 4.5 15 15m0-15-15 15" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="lc-empty-state my-4">
                                    <div class="lc-empty-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.75h9" />
                                        </svg>
                                    </div>
                                    <div class="lc-empty-title">No se encontraron proveedores</div>
                                    <p class="lc-empty-copy">Intenta con otra búsqueda o registra un proveedor nuevo para empezar a abastecer insumos.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($proveedores->hasPages())
            <div class="lc-pagination-shell">{{ $proveedores->links() }}</div>
        @endif
    </section>
</div>
@endsection
