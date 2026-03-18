@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start gap-3.5 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                @if($userRole === 'ALMACEN')
                    Gestión de Entregas (Almacén)
                @elseif(in_array($userRole ?? '', ['ADMIN']))
                    Entregas (Administración)
                @else
                    Mis entregas (Proveedor)
                @endif
            </h1>
            <p class="text-slate-500 mt-1.5 max-w-[760px]">
                @if($userRole === 'ALMACEN')
                    Gestiona la recepción y registro de materias primas entregadas por proveedores.
                @elseif(in_array($userRole ?? '', ['ADMIN']))
                    Supervisa todas las entregas de proveedores en el sistema.
                @else
                    Registra entregas de insumos para abastecer la producción de artículos de acampar.
                @endif
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-3.5 border border-red-200 bg-red-50 text-red-800 rounded-xl p-3 text-sm flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            {{ $errors->first() }}
        </div>
    @endif

    @if (! $proveedor && !in_array($userRole ?? '', ['ADMIN', 'ALMACEN']))
        <div class="border border-amber-200 rounded-xl p-4 bg-amber-50 text-amber-800 shadow-sm mb-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium text-sm text-amber-900">Tu usuario no está vinculado a un proveedor. Pide al administrador crear una ficha de proveedor con tu mismo correo.</span>
            </div>
        </div>
    @else
        <section class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm mb-4">
            <h2 class="text-lg font-bold text-slate-900 mb-3.5">
                @if($userRole === 'ALMACEN')
                    Registrar recepción de material
                @else
                    Registrar entrega
                @endif
            </h2>
            <form method="POST" action="{{ route('entregas.store') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                    <div class="flex flex-col">
                        <label for="material_id" class="block mb-1.5 text-xs font-bold text-slate-700">Material entregado</label>
                        <select id="material_id" name="material_id" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all outline-none">
                            <option value="">Selecciona un material</option>
                            @foreach ($materiales as $material)
                                <option value="{{ $material->id }}" {{ (string) old('material_id') === (string) $material->id ? 'selected' : '' }}>
                                    {{ $material->nombre }}
                                    @if(in_array($userRole ?? '', ['ADMIN', 'ALMACEN']) && $material->proveedor)
                                        ({{ $material->proveedor->nombre }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label for="orden_compra_id" class="block mb-1.5 text-xs font-bold text-slate-700">Orden de compra (opcional)</label>
                        <select id="orden_compra_id" name="orden_compra_id" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                            <option value="">Sin orden específica</option>
                            @foreach ($ordenes as $orden)
                                <option value="{{ $orden->id }}" {{ (string) old('orden_compra_id') === (string) $orden->id ? 'selected' : '' }}>
                                    #{{ $orden->id }} - {{ $orden->fecha }}
                                    @if(in_array($userRole ?? '', ['ADMIN', 'ALMACEN']) && $orden->proveedor)
                                        ({{ $orden->proveedor->nombre }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label for="fecha_entrega" class="block mb-1.5 text-xs font-bold text-slate-700">Fecha de entrega</label>
                        <input id="fecha_entrega" name="fecha_entrega" type="datetime-local" value="{{ old('fecha_entrega') }}" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div class="flex flex-col">
                        <label for="cantidad_entregada" class="block mb-1.5 text-xs font-bold text-slate-700">Cantidad entregada</label>
                        <input id="cantidad_entregada" name="cantidad_entregada" type="number" step="0.01" min="0.01" value="{{ old('cantidad_entregada') }}" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div class="flex flex-col">
                        <label for="estado_calidad" class="block mb-1.5 text-xs font-bold text-slate-700">Estado de calidad</label>
                        <select id="estado_calidad" name="estado_calidad" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            @php $oldCalidad = old('estado_calidad', 'ACEPTADO'); @endphp
                            <option value="ACEPTADO" {{ $oldCalidad === 'ACEPTADO' ? 'selected' : '' }}>ACEPTADO</option>
                            <option value="OBSERVADO" {{ $oldCalidad === 'OBSERVADO' ? 'selected' : '' }}>OBSERVADO</option>
                            <option value="RECHAZADO" {{ $oldCalidad === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex flex-col">
                    <label for="observaciones" class="block mb-1.5 text-xs font-bold text-slate-700">Observaciones (opcional)</label>
                    <textarea id="observaciones" name="observaciones" rows="2" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">{{ old('observaciones') }}</textarea>
                </div>

                <button type="submit" class="mt-3 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded-lg text-sm shadow-sm transition-all active:scale-95">
                    @if($userRole === 'ALMACEN')
                        Registrar recepción
                    @else
                        Registrar entrega
                    @endif
                </button>
            </form>
        </section>
    @endif

    <section class="border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden">
        <h2 class="text-lg font-bold text-slate-900 p-4 border-b border-slate-50">
            @if($userRole === 'ALMACEN')
                Registro de recepciones
            @elseif(in_array($userRole ?? '', ['ADMIN']))
                Todas las entregas registradas
            @else
                Historial de entregas registradas
            @endif
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[740px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">ID</th>
                        @if(in_array($userRole ?? '', ['ADMIN', 'ALMACEN']))
                            <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Proveedor</th>
                            <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Usuario</th>
                        @endif
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Material</th>
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider text-center">Orden</th>
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Fecha entrega</th>
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Cantidad</th>
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider text-center">Calidad</th>
                        <th class="p-3 text-[0.8rem] text-slate-500 uppercase font-semibold tracking-wider">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($entregas as $entrega)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 font-mono text-xs text-slate-400">#{{ $entrega->id }}</td>
                            @if(in_array($userRole ?? '', ['ADMIN', 'ALMACEN']))
                                <td class="p-3 font-medium text-slate-900">{{ $entrega->proveedor?->nombre ?? '-' }}</td>
                                <td class="p-3 italic text-slate-600">{{ $entrega->usuario?->nombre ?? '-' }}</td>
                            @endif
                            <td class="p-3 font-medium text-slate-900">{{ $entrega->material->nombre ?? '-' }}</td>
                            <td class="p-3 text-center text-slate-600 font-mono italic">{{ $entrega->orden_compra_id ?: '-' }}</td>
                            <td class="p-3 text-slate-600 tracking-tight">{{ optional($entrega->fecha_entrega)->format('Y-m-d H:i') }}</td>
                            <td class="p-3 font-bold text-slate-800">{{ $entrega->cantidad_entregada }}</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[0.7rem] font-bold border 
                                    {{ $entrega->estado_calidad === 'ACEPTADO' ? 'bg-green-50 text-green-700 border-green-200' : 
                                       ($entrega->estado_calidad === 'RECHAZADO' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                    {{ $entrega->estado_calidad }}
                                </span>
                            </td>
                            <td class="p-3 text-slate-500 italic max-w-xs truncate">{{ $entrega->observaciones ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ in_array($userRole ?? '', ['ADMIN', 'ALMACEN']) ? '9' : '7' }}" class="p-8 text-center text-slate-400 italic bg-slate-50/50">
                                @if($userRole === 'ALMACEN')
                                    No hay recepciones registradas aún.
                                @elseif(in_array($userRole ?? '', ['ADMIN']))
                                    No hay entregas registradas en el sistema.
                                @else
                                    Aún no registras entregas.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection