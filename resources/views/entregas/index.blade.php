@extends('layouts.app')

@section('content')
@php
    $role = strtoupper((string) ($userRole ?? ''));
    $isAdminRole = in_array($role, ['ADMIN', 'SUPER_ADMIN', 'SUPER-ADMIN', 'SUPER ADMIN', 'SUPER ADMINISTRADOR'], true);
    $isAlmacenRole = in_array($role, ['ALMACEN', 'ALMACÉN'], true);
    $canReviewEntregas = $isAdminRole || $isAlmacenRole;
@endphp

<div class="container mx-auto px-4 py-6 space-y-6">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-800">
            @if($isAlmacenRole)
                Gestión de Entregas
            @elseif($isAdminRole)
                Supervisión de Entregas
            @else
                Mis Entregas
            @endif
        </h1>
        <p class="text-slate-500 text-sm mt-1 max-w-3xl">
            @if($isAlmacenRole)
                Registra recepciones y controla la revisión de materiales entregados.
            @elseif($isAdminRole)
                Consulta y valida las entregas registradas en el sistema.
            @else
                Consulta el historial de entregas registradas para tus materiales.
            @endif
        </p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($isAlmacenRole)
        <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-bold text-slate-800">Registrar recepción</h2>
                <p class="text-slate-500 text-sm mt-1">Ingresa una nueva recepción de material para actualizar el inventario.</p>
            </div>
            <form method="POST" action="{{ route('entregas.store') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                @csrf
                <div class="flex flex-col gap-1.5 xl:col-span-2">
                    <label for="material_id" class="text-sm font-semibold text-slate-600">Material entregado</label>
                    <select id="material_id" name="material_id" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Selecciona un material</option>
                        @foreach ($materiales as $material)
                            <option value="{{ $material->id }}" {{ (string) old('material_id') === (string) $material->id ? 'selected' : '' }}>
                                {{ $material->nombre }}@if($material->proveedor) ({{ $material->proveedor->nombre }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5 xl:col-span-2">
                    <label for="orden_compra_id" class="text-sm font-semibold text-slate-600">Orden de compra</label>
                    <select id="orden_compra_id" name="orden_compra_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Sin orden específica</option>
                        @foreach ($ordenes as $orden)
                            <option value="{{ $orden->id }}" {{ (string) old('orden_compra_id') === (string) $orden->id ? 'selected' : '' }}>
                                #{{ $orden->id }} - {{ $orden->fecha }}@if($orden->proveedor) ({{ $orden->proveedor->nombre }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="fecha_entrega" class="text-sm font-semibold text-slate-600">Fecha de entrega</label>
                    <input id="fecha_entrega" name="fecha_entrega" type="datetime-local" value="{{ old('fecha_entrega') }}" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="cantidad_entregada" class="text-sm font-semibold text-slate-600">Cantidad</label>
                    <input id="cantidad_entregada" name="cantidad_entregada" type="number" step="0.01" min="0.01" value="{{ old('cantidad_entregada') }}" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="estado_calidad" class="text-sm font-semibold text-slate-600">Calidad</label>
                    <select id="estado_calidad" name="estado_calidad" required class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        @php $oldCalidad = old('estado_calidad', 'ACEPTADO'); @endphp
                        <option value="ACEPTADO" {{ $oldCalidad === 'ACEPTADO' ? 'selected' : '' }}>ACEPTADO</option>
                        <option value="OBSERVADO" {{ $oldCalidad === 'OBSERVADO' ? 'selected' : '' }}>OBSERVADO</option>
                        <option value="RECHAZADO" {{ $oldCalidad === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-5 flex flex-col gap-1.5">
                    <label for="observaciones" class="text-sm font-semibold text-slate-600">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">{{ old('observaciones') }}</textarea>
                </div>

                <div class="xl:col-span-5">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm">Registrar recepción</button>
                </div>
            </form>
        </section>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">Entregas registradas</h2>
            <p class="text-slate-500 text-sm mt-1">Listado consolidado de entregas con estado de calidad y revisión.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1100px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">ID</th>
                        @if($canReviewEntregas)
                            <th class="p-4 text-xs font-bold uppercase">Proveedor</th>
                            <th class="p-4 text-xs font-bold uppercase">Usuario</th>
                        @endif
                        <th class="p-4 text-xs font-bold uppercase">Material</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Orden</th>
                        <th class="p-4 text-xs font-bold uppercase">Fecha</th>
                        <th class="p-4 text-xs font-bold uppercase">Cantidad</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Calidad</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Revisión</th>
                        <th class="p-4 text-xs font-bold uppercase">Observaciones</th>
                        @if($canReviewEntregas)
                            <th class="p-4 text-xs font-bold uppercase text-center">Acción</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse ($entregas as $entrega)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-mono text-xs text-slate-400">#{{ $entrega->id }}</td>
                            @if($canReviewEntregas)
                                <td class="p-4 font-semibold">{{ $entrega->proveedor?->nombre ?? '-' }}</td>
                                <td class="p-4">{{ $entrega->usuario?->nombre ?? '-' }}</td>
                            @endif
                            <td class="p-4">{{ $entrega->material->nombre ?? '-' }}</td>
                            <td class="p-4 text-center">{{ $entrega->orden_compra_id ?: '-' }}</td>
                            <td class="p-4">{{ optional($entrega->fecha_entrega)->format('Y-m-d H:i') }}</td>
                            <td class="p-4 font-bold">{{ $entrega->cantidad_entregada }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold border {{ $entrega->estado_calidad === 'ACEPTADO' ? 'bg-green-50 text-green-700 border-green-200' : ($entrega->estado_calidad === 'RECHAZADO' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                    {{ $entrega->estado_calidad }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $entrega->estado_revision === 'APROBADO' ? 'bg-green-100 text-green-700' : ($entrega->estado_revision === 'RECHAZADO' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $entrega->estado_revision }}
                                </span>
                            </td>
                            <td class="p-4 max-w-xs truncate text-slate-500">{{ $entrega->observaciones ?: '-' }}</td>
                            @if($canReviewEntregas)
                                <td class="p-4">
                                    <form method="POST" action="{{ route('compras.entregas.revision', $entrega->id) }}" class="grid grid-cols-1 gap-2 min-w-[180px]">
                                        @csrf
                                        <select name="estado_revision" class="text-xs border border-slate-300 rounded-lg p-2 bg-white focus:ring-2 focus:ring-sky-500 outline-none">
                                            <option value="APROBADO" {{ $entrega->estado_revision === 'APROBADO' ? 'selected' : '' }}>APROBADO</option>
                                            <option value="RECHAZADO" {{ $entrega->estado_revision === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                                        </select>
                                        <input type="text" name="observacion_revision" value="{{ $entrega->observacion_revision }}" placeholder="Observación" class="text-xs border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-sky-500 outline-none">
                                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold py-2 rounded-lg">Guardar</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canReviewEntregas ? '11' : '8' }}" class="p-8 text-center text-slate-500">No hay entregas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@endsection