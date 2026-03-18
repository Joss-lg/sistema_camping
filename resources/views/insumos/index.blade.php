@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-start gap-3.5 mb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 font-sans">Control de Insumos</h1>
            <p class="text-slate-500 mt-1.5 max-w-[760px] text-sm md:text-base">
                Administra los materiales que luego se usan en compras, entregas de proveedor y producción.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 mb-4">
        <article class="border border-slate-200 rounded-xl p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-xs font-medium uppercase tracking-wider">Materiales registrados</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $statsTotal }}</div>
        </article>
        <article class="border border-slate-200 rounded-xl p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-xs font-medium uppercase tracking-wider">En alerta (<= min)</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $statsBajoMinimo }}</div>
        </article>
        <article class="border border-slate-200 rounded-xl p-3 bg-gradient-to-br from-white to-slate-50 shadow-sm">
            <div class="text-slate-500 text-xs font-medium uppercase tracking-wider">Sin stock</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ $statsSinStock }}</div>
        </article>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 border border-red-200 bg-rose-50 text-rose-800 rounded-lg text-sm shadow-sm">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <strong class="font-bold">Revisa los datos:</strong>
            </div>
            <ul class="list-disc list-inside ml-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm mb-4">
        <h2 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-wide">Filtros de consulta</h2>
        <form method="GET" action="{{ route('insumos.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700">Buscar material</label>
                    <input name="q" type="text" value="{{ $q }}" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700">Proveedor</label>
                    <select name="proveedor_id" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Todos</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected($selectedProveedor === (int) $proveedor->id)>{{ $proveedor->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700">Categoría</label>
                    <select name="categoria_id" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected($selectedCategoria === (int) $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-700">Estado stock</label>
                    <select name="estado_stock" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="" @selected($selectedEstadoStock === '')>Todos</option>
                        <option value="ok" @selected($selectedEstadoStock === 'ok')>Stock saludable</option>
                        <option value="bajo" @selected($selectedEstadoStock === 'bajo')>En alerta</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors shadow-sm active:scale-95">Aplicar filtros</button>
                <a href="{{ route('insumos.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold py-2 px-4 rounded-lg text-sm transition-colors text-center">Limpiar</a>
            </div>
        </form>
    </section>

    @if ($canManage)
        <section class="border border-slate-200 rounded-xl p-4 bg-white shadow-sm mb-4">
            <h2 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-wide">Registrar nuevo insumo</h2>
            <form method="POST" action="{{ route('insumos.store') }}">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Nombre</label>
                        <input name="nombre" type="text" value="{{ old('nombre') }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Categoría</label>
                        <select name="categoria_id" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Selecciona</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected((int) old('categoria_id') === (int) $categoria->id)>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Unidad</label>
                        <select name="unidad_id" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Selecciona</option>
                            @foreach ($unidades as $unidad)
                                <option value="{{ $unidad->id }}" @selected((int) old('unidad_id') === (int) $unidad->id)>{{ $unidad->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Proveedor</label>
                        <select name="proveedor_id" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Sin proveedor</option>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id') === (int) $proveedor->id)>{{ $proveedor->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Stock actual</label>
                        <input name="stock" type="number" step="0.01" value="{{ old('stock', 0) }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Stock mínimo</label>
                        <input name="stock_minimo" type="number" step="0.01" value="{{ old('stock_minimo', 0) }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-slate-700">Stock máximo</label>
                        <input name="stock_maximo" type="number" step="0.01" value="{{ old('stock_maximo', 0) }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded-lg text-sm transition-all shadow-sm active:scale-95">Guardar insumo</button>
                </div>
            </form>
        </section>
    @endif

    <section class="border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden">
        <h2 class="text-sm font-bold text-slate-800 p-4 border-b border-slate-100 uppercase tracking-wide">Listado de insumos</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider">Material</th>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider">Categoría</th>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider">Unidad</th>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider text-right">Stock</th>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider text-right">Mín/Máx</th>
                        <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider text-center">Estado</th>
                        @if ($canManage) <th class="p-3 text-[0.75rem] text-slate-500 font-bold uppercase tracking-wider text-center">Acciones</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($materiales as $material)
                        @php
                            $isEmpty = (float) $material->stock <= 0;
                            $isLow = (float) $material->stock <= (float) $material->stock_minimo;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-slate-900">{{ $material->nombre }}</div>
                                <div class="text-[0.7rem] text-slate-400 font-mono">{{ $material->proveedor?->nombre ?? 'Sin proveedor' }}</div>
                            </td>
                            <td class="p-3 text-slate-600">{{ $material->categoria?->nombre ?? '-' }}</td>
                            <td class="p-3 text-slate-600 text-xs italic">{{ $material->unidad?->nombre ?? '-' }}</td>
                            <td class="p-3 text-right font-mono font-bold text-slate-800">{{ number_format((float) $material->stock, 2) }}</td>
                            <td class="p-3 text-right text-xs text-slate-500 font-mono">
                                {{ number_format((float) $material->stock_minimo, 1) }} / {{ number_format((float) $material->stock_maximo, 1) }}
                            </td>
                            <td class="p-3 text-center">
                                @if ($isEmpty)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[0.7rem] font-bold bg-red-100 text-red-700 border border-red-200">Sin stock</span>
                                @elseif ($isLow)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[0.7rem] font-bold bg-amber-100 text-amber-700 border border-amber-200">Bajo mínimo</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[0.7rem] font-bold bg-green-100 text-green-700 border border-green-200">Estable</span>
                                @endif
                            </td>
                            @if ($canManage)
                                <td class="p-3 text-center">
                                    <button onclick="openEditModal('edit-material-{{ $material->id }}')" class="bg-amber-100 hover:bg-amber-500 text-amber-700 hover:text-white font-bold py-1 px-3 rounded text-xs transition-all">Editar</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400 italic">No hay insumos registrados con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $materiales->links() }}
        </div>
    </section>

    @if ($canManage)
        @foreach ($materiales as $material)
            <dialog id="edit-material-{{ $material->id }}" class="rounded-xl border border-slate-300 shadow-2xl p-0 w-[min(760px,95vw)] backdrop:bg-slate-900/50">
                <div class="flex justify-between items-center p-4 border-b border-slate-100 bg-slate-50">
                    <strong class="text-slate-800">Editar insumo: {{ $material->nombre }}</strong>
                    <button onclick="closeEditModal('edit-material-{{ $material->id }}')" class="text-slate-400 hover:text-slate-600 text-xl font-bold px-2">&times;</button>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('insumos.update', $material) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Nombre</label>
                                <input name="nombre" type="text" value="{{ $material->nombre }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Categoría</label>
                                <select name="categoria_id" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected((int) $material->categoria_id === (int) $categoria->id)>{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Unidad</label>
                                <select name="unidad_id" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                                    @foreach ($unidades as $unidad)
                                        <option value="{{ $unidad->id }}" @selected((int) $material->unidad_id === (int) $unidad->id)>{{ $unidad->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Proveedor</label>
                                <select name="proveedor_id" class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                                    <option value="">Sin proveedor</option>
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}" @selected((int) $material->proveedor_id === (int) $proveedor->id)>{{ $proveedor->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-700">Stock actual</label>
                                <input name="stock" type="number" step="0.01" value="{{ $material->stock }}" required class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-bold text-slate-700">Mínimo / Máximo</label>
                                    <div class="flex gap-2">
                                        <input name="stock_minimo" type="number" step="0.01" value="{{ $material->stock_minimo }}" class="w-1/2 border border-slate-300 rounded-lg p-2 text-sm outline-none">
                                        <input name="stock_maximo" type="number" step="0.01" value="{{ $material->stock_maximo }}" class="w-1/2 border border-slate-300 rounded-lg p-2 text-sm outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-6">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg text-sm transition-all active:scale-95 shadow-sm">Guardar cambios</button>
                            <button type="button" onclick="closeEditModal('edit-material-{{ $material->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-6 rounded-lg text-sm font-bold">Cancelar</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endforeach
    @endif

    <script>
        function openEditModal(id) {
            const modal = document.getElementById(id);
            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }
        }

        function closeEditModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.close();
            }
        }
    </script>
@endsection