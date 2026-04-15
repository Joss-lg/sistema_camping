@extends('layouts.app')

@section('content')
<div class="lc-page">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Almacenes</h1>
            <p class="lc-subtitle">Administra ubicaciones fisicas para mantener trazabilidad y asignacion correcta de stock.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="lc-badge lc-badge-neutral">{{ method_exists($ubicaciones, 'total') ? $ubicaciones->total() : $ubicaciones->count() }} ubicaciones</span>
            <a href="{{ route('almacenes.create') }}" class="lc-btn-primary">Nueva ubicacion</a>
        </div>
    </section>

    @include('partials.flash-messages')

    <section class="lc-toolbar">
        <div>
            <h2 class="lc-section-title">Filtros</h2>
            <p class="lc-section-subtitle">Busca por codigo o nombre y filtra por tipo y estado.</p>
        </div>
        <form method="GET" action="{{ route('almacenes.index') }}" class="grid w-full gap-3 lg:grid-cols-[1fr_220px_180px_auto]">
            <input type="text" name="q" value="{{ $q }}" placeholder="Codigo, nombre o seccion" class="lc-input">

            <select name="tipo" class="lc-select">
                <option value="">Todos los tipos</option>
                @foreach ($tiposCatalogo as $itemTipo)
                    <option value="{{ $itemTipo }}" @selected($tipo === $itemTipo)>{{ $itemTipo }}</option>
                @endforeach
            </select>

            <select name="estado" class="lc-select">
                <option value="">Todos</option>
                <option value="activo" @selected($estado === 'activo')>Activos</option>
                <option value="inactivo" @selected($estado === 'inactivo')>Inactivos</option>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="lc-btn-secondary">Filtrar</button>
                @if($q !== '' || $tipo !== '' || $estado !== '')
                    <a href="{{ route('almacenes.index') }}" class="lc-btn-secondary">Limpiar</a>
                @endif
            </div>
        </form>
    </section>

    <section class="lc-card overflow-hidden">
        <div class="lc-table-wrap lc-scrollbar">
            <table class="lc-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Ubicacion fisica</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ubicaciones as $ubicacion)
                        <tr>
                            <td>
                                <div class="font-mono text-xs font-semibold text-slate-600">{{ $ubicacion->codigo_ubicacion }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $ubicacion->nombre }}</div>
                            </td>
                            <td>
                                <span class="lc-badge lc-badge-neutral">{{ $ubicacion->tipo }}</span>
                            </td>
                            <td>
                                <div class="text-sm text-slate-600">
                                    {{ $ubicacion->seccion ?: '-' }}
                                    / Est. {{ $ubicacion->estante ?: '-' }}
                                    / Niv. {{ $ubicacion->nivel ?: '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-sm text-slate-700">
                                    Actual: <span class="font-semibold">{{ number_format((float)(($ubicacion->stock_insumos ?? 0) + ($ubicacion->stock_terminados ?? 0)), 2) }}</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    Max: {{ $ubicacion->capacidad_maxima !== null ? number_format((float) $ubicacion->capacidad_maxima, 2) : 'Sin limite' }}
                                </div>
                            </td>
                            <td>
                                <span class="{{ (bool) $ubicacion->activo ? 'lc-badge lc-badge-success' : 'lc-badge lc-badge-neutral' }}">
                                    {{ (bool) $ubicacion->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="lc-table-actions">
                                    <a href="{{ route('almacenes.edit', $ubicacion->id) }}" class="lc-icon-btn lc-icon-btn-warning" title="Editar ubicacion" aria-label="Editar ubicacion {{ $ubicacion->nombre }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('almacenes.toggle-estado', $ubicacion->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="lc-icon-btn {{ (bool) $ubicacion->activo ? 'lc-icon-btn-danger' : 'lc-icon-btn-info' }}" title="{{ (bool) $ubicacion->activo ? 'Desactivar' : 'Activar' }} ubicacion" aria-label="{{ (bool) $ubicacion->activo ? 'Desactivar' : 'Activar' }} ubicacion {{ $ubicacion->nombre }}">
                                            @if((bool) $ubicacion->activo)
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
                                    <div class="lc-empty-title">No se encontraron ubicaciones</div>
                                    <p class="lc-empty-copy">Registra una ubicacion para asignar stock de insumos y terminados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ubicaciones->hasPages())
            <div class="lc-pagination-shell">{{ $ubicaciones->links() }}</div>
        @endif
    </section>
</div>
@endsection
