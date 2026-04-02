@extends('layouts.app')

@section('content')
@php
    $role = strtoupper((string) ($userRole ?? ''));
    $isAdminRole = in_array($role, ['ADMIN', 'SUPER_ADMIN', 'SUPER-ADMIN', 'SUPER ADMIN', 'SUPER ADMINISTRADOR'], true);
    $isAlmacenRole = in_array($role, ['ALMACEN', 'ALMACÉN'], true);
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
                Registra recepciones y controla la revisión de materiales entregados por proveedores.
            @elseif($isAdminRole)
                Consulta, valida y supervisa todas las entregas registradas en el sistema.
            @else
                Historial de entregas de materiales que has solicitado.
            @endif
        </p>
    </div>

    @include('partials.flash-messages')

    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">
                @if($role === 'PROVEEDOR')
                    Mis pedidos recibidos por la empresa
                @else
                    Compras recibidas por la empresa
                @endif
            </h2>
            <p class="text-slate-500 text-sm mt-1">Listado de órdenes de compra confirmadas como recibidas en el módulo de compras.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500">
                        <th class="p-4 text-xs font-bold uppercase">Orden</th>
                        <th class="p-4 text-xs font-bold uppercase">Proveedor</th>
                        <th class="p-4 text-xs font-bold uppercase">Fecha orden</th>
                        <th class="p-4 text-xs font-bold uppercase">Fecha recepción</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase text-right">Monto total</th>
                        <th class="p-4 text-xs font-bold uppercase text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($ordenesRecibidas as $ordenRecibida)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-semibold">{{ $ordenRecibida->numero_orden ?: ('#' . $ordenRecibida->id) }}</td>
                            <td class="p-4">{{ $ordenRecibida->proveedor->nombre ?? '-' }}</td>
                            <td class="p-4">{{ $ordenRecibida->fecha_orden }}</td>
                            <td class="p-4">{{ $ordenRecibida->fecha_entrega_real }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-green-100 text-green-700">{{ $ordenRecibida->estado }}</span>
                            </td>
                            <td class="p-4 text-right font-semibold">{{ number_format((float) $ordenRecibida->monto_total, 2) }}</td>
                            <td class="p-4 text-center">
                                @if($ordenRecibida->url_show)
                                    <a href="{{ $ordenRecibida->url_show }}" class="bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Ver orden</a>
                                @else
                                    <span class="text-xs text-slate-400">Sin acceso</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">No hay órdenes de compra recibidas para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($isAlmacenRole)
        <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
            <div class="mb-6 pb-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800">Registrar nueva recepción</h2>
                <p class="text-slate-500 text-sm mt-1">Completa los datos para registrar la llegada de un material.</p>
            </div>
            <form method="POST" action="{{ route('entregas.store') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
                @csrf
                <div class="flex flex-col gap-2 xl:col-span-2">
                    <label for="material_id" class="text-sm font-semibold text-slate-700">Material entregado</label>
                    <select id="material_id" name="material_id" required class="border border-slate-300 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring cursor-pointer">
                        <option value="">Selecciona un material</option>
                        @foreach ($materiales as $material)
                            <option value="{{ $material->id }}" {{ (string) old('material_id') === (string) $material->id ? 'selected' : '' }}>
                                {{ $material->nombre }}@if($material->proveedor) ({{ $material->proveedor->nombre }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-2 xl:col-span-2">
                    <label for="orden_compra_id" class="text-sm font-semibold text-slate-700">Orden de compra</label>
                    <select id="orden_compra_id" name="orden_compra_id" class="border border-slate-300 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring cursor-pointer">
                        <option value="">Sin orden específica</option>
                        @foreach ($ordenes as $orden)
                            <option value="{{ $orden->id }}" {{ (string) old('orden_compra_id') === (string) $orden->id ? 'selected' : '' }}>
                                #{{ $orden->id }} - {{ $orden->fecha }}@if($orden->proveedor) ({{ $orden->proveedor->nombre }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="fecha_entrega" class="text-sm font-semibold text-slate-700">Fecha de entrega</label>
                    <input id="fecha_entrega" name="fecha_entrega" type="datetime-local" value="{{ old('fecha_entrega') }}" required class="border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="cantidad_entregada" class="text-sm font-semibold text-slate-700">Cantidad</label>
                    <input id="cantidad_entregada" name="cantidad_entregada" type="number" step="0.01" min="0.01" value="{{ old('cantidad_entregada') }}" required class="border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="estado_calidad" class="text-sm font-semibold text-slate-700">Calidad</label>
                    <select id="estado_calidad" name="estado_calidad" required class="border border-slate-300 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring cursor-pointer">
                        @php $oldCalidad = old('estado_calidad', 'ACEPTADO'); @endphp
                        <option value="ACEPTADO" {{ $oldCalidad === 'ACEPTADO' ? 'selected' : '' }}>ACEPTADO</option>
                        <option value="OBSERVADO" {{ $oldCalidad === 'OBSERVADO' ? 'selected' : '' }}>OBSERVADO</option>
                        <option value="RECHAZADO" {{ $oldCalidad === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                    </select>
                </div>

                <div class="xl:col-span-5 flex flex-col gap-2">
                    <label for="observaciones" class="text-sm font-semibold text-slate-700">Observaciones (opcional)</label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring resize-none">{{ old('observaciones') }}</textarea>
                </div>

                <div class="xl:col-span-5">
                    <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Registrar recepción
                    </button>
                </div>
            </form>
        </section>
    @endif

</div>

@endsection