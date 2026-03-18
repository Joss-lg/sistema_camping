@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Compras (Soporte operativo)</h1>
    <p class="text-slate-500 mb-6">Gestiona abastecimiento de insumos para productos de acampar y revisa entregas del proveedor.</p>

    @if (! $isAdmin)
        <div class="mt-4 border border-slate-200 rounded-xl p-4 bg-slate-50">
            <strong class="text-slate-900">Acceso limitado</strong>
            <p class="mt-1.5 text-slate-600 text-sm">Tu perfil puede consultar este módulo, pero no tiene permisos para acciones de edición en compras.</p>
        </div>
    @else
        <div class="mt-4 border border-slate-200 rounded-xl p-5 bg-white shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Reabastecimiento sugerido por stock bajo</h2>

            @if ($insumosCriticos->isEmpty())
                <p class="text-slate-500 italic">No hay insumos críticos en este momento.</p>
            @else
                <form method="POST" action="{{ route('compras.ordenes.sugeridas') }}">
                    @csrf
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-700">
                                    <th class="p-3 font-semibold">Sel</th>
                                    <th class="p-3 font-semibold">Material</th>
                                    <th class="p-3 font-semibold">Proveedor</th>
                                    <th class="p-3 font-semibold text-center">Stock</th>
                                    <th class="p-3 font-semibold text-center">Mínimo</th>
                                    <th class="p-3 font-semibold text-center text-green-700">Sugerido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @foreach ($insumosCriticos as $row)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-3 text-center">
                                            <input type="checkbox" name="material_ids[]" value="{{ $row['material']->id }}" checked 
                                                class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500">
                                        </td>
                                        <td class="p-3 font-medium text-slate-900">{{ $row['material']->nombre }}</td>
                                        <td class="p-3 text-slate-600">{{ $row['material']->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                                        <td class="p-3 text-center font-mono">{{ number_format((float) $row['material']->stock, 2) }}</td>
                                        <td class="p-3 text-center font-mono text-red-500">{{ number_format((float) $row['material']->stock_minimo, 2) }}</td>
                                        <td class="p-3 text-center font-bold text-green-600 bg-green-50/50 italic font-mono">
                                            {{ number_format((float) $row['cantidad_sugerida'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="mt-4 inline-flex items-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-all shadow-sm active:scale-95">
                        Generar órdenes de compra sugeridas
                    </button>
                </form>
            @endif
        </div>

        <h2 id="ordenes-compra" class="mt-8 mb-4 text-xl font-bold text-slate-900">Órdenes de compra pendientes</h2>

        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-sm bg-white">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-700 uppercase tracking-wider">
                        <th class="p-4 font-semibold">ID</th>
                        <th class="p-4 font-semibold">Proveedor</th>
                        <th class="p-4 font-semibold">Usuario</th>
                        <th class="p-4 font-semibold">Fecha</th>
                        <th class="p-4 font-semibold">Fecha esperada</th>
                        <th class="p-4 font-semibold">Estado</th>
                        <th class="p-4 font-semibold">Materiales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    @forelse ($ordenesCompra as $orden)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4 font-mono text-xs text-slate-400">#{{ $orden->id }}</td>
                            <td class="p-4 font-semibold text-slate-900">{{ $orden->proveedor->nombre ?? '-' }}</td>
                            <td class="p-4 italic">{{ $orden->usuario->nombre ?? '-' }}</td>
                            <td class="p-4">{{ $orden->fecha->format('Y-m-d') }}</td>
                            <td class="p-4">{{ $orden->fecha_esperada->format('Y-m-d') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $orden->estado->nombre === 'PENDIENTE' ? 'bg-amber-100 text-amber-800' : 
                                       ($orden->estado->nombre === 'APROBADO' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $orden->estado->nombre }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="space-y-1">
                                    @foreach ($orden->items as $item)
                                        <div class="text-xs">
                                            <span class="font-medium">{{ $item->material->nombre }}</span>
                                            <span class="text-slate-500">({{ number_format($item->cantidad, 2) }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 italic bg-slate-50/30">No hay órdenes de compra pendientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 mb-4 text-xl font-bold text-slate-900">Revisión de entregas registradas</h2>

        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-sm bg-white">
            <table class="w-full text-left border-collapse min-w-[1050px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-700 uppercase tracking-wider">
                        <th class="p-4 font-semibold">ID</th>
                        <th class="p-4 font-semibold">Proveedor</th>
                        <th class="p-4 font-semibold">Usuario</th>
                        <th class="p-4 font-semibold">Material</th>
                        <th class="p-4 font-semibold">Orden</th>
                        <th class="p-4 font-semibold">Fecha entrega</th>
                        <th class="p-4 font-semibold">Cantidad</th>
                        <th class="p-4 font-semibold">Calidad</th>
                        <th class="p-4 font-semibold text-center">Revisión Admin</th>
                        <th class="p-4 font-semibold text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    @forelse ($entregas as $entrega)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4 font-mono text-xs text-slate-400">#{{ $entrega->id }}</td>
                            <td class="p-4 font-semibold text-slate-900">{{ $entrega->proveedor->nombre ?? '-' }}</td>
                            <td class="p-4 italic">{{ $entrega->usuario->nombre ?? '-' }}</td>
                            <td class="p-4">{{ $entrega->material->nombre ?? '-' }}</td>
                            <td class="p-4">{{ $entrega->orden_compra_id ?: '-' }}</td>
                            <td class="p-4">{{ optional($entrega->fecha_entrega)->format('Y-m-d H:i') }}</td>
                            <td class="p-4 font-bold">{{ $entrega->cantidad_entregada }}</td>
                            <td class="p-4 text-xs">
                                <span class="px-2 py-1 rounded-full bg-slate-100 border border-slate-200 font-medium">
                                    {{ $entrega->estado_calidad }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col items-center">
                                    <span class="font-bold px-2 py-1 rounded {{ $entrega->estado_revision === 'APROBADO' ? 'text-green-700 bg-green-50' : ($entrega->estado_revision === 'RECHAZADO' ? 'text-red-700 bg-red-50' : 'text-amber-700 bg-amber-50') }}">
                                        {{ $entrega->estado_revision }}
                                    </span>
                                    @if ($entrega->revisor)
                                        <div class="text-[0.75rem] text-slate-400 mt-1 italic">por {{ $entrega->revisor->nombre }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4">
                                <form method="POST" action="{{ route('compras.entregas.revision', $entrega->id) }}" class="flex flex-col gap-2 w-48 mx-auto">
                                    @csrf
                                    <select name="estado_revision" class="w-full text-xs border border-slate-200 rounded-md p-2 bg-white focus:ring-sky-500 focus:border-sky-500">
                                        <option value="APROBADO" {{ $entrega->estado_revision === 'APROBADO' ? 'selected' : '' }}>APROBADO</option>
                                        <option value="RECHAZADO" {{ $entrega->estado_revision === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                                    </select>
                                    <input type="text" name="observacion_revision" value="{{ $entrega->observacion_revision }}" 
                                        placeholder="Observación" 
                                        class="w-full text-xs border border-slate-200 rounded-md p-2 focus:ring-sky-500 focus:border-sky-500">
                                    <button type="submit" class="w-full text-xs font-bold py-2 bg-sky-500 hover:bg-sky-600 text-white rounded-md transition-colors">
                                        Guardar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400 italic bg-slate-50/30">Aún no hay entregas registradas por proveedores.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection