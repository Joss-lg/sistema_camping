@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold text-slate-800">1.1 Ordenes y receta de producción</h1>
        <p class="text-slate-500">Define la receta (BOM) de materiales para fabricar el catálogo de productos de acampar.</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="mt-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl shadow-sm">
            <span class="font-bold">Error:</span> {{ $errors->first() }}
        </div>
    @endif

    {{-- Estado de Permisos / Formulario --}}
    @if (! $canManage)
        <div class="mt-6 bg-slate-50 border border-slate-200 rounded-xl p-5 flex items-start gap-4">
            <div class="bg-slate-200 p-2 rounded-full">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2m8-3V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-3z"></path></svg>
            </div>
            <div>
                <strong class="text-slate-800 block">Acceso limitado</strong>
                <p class="text-slate-500 text-sm">Tu perfil puede consultar la receta, pero no tiene permisos para editar líneas de materiales.</p>
            </div>
        </div>
    @else
        <div class="mt-6 bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="w-2 h-6 bg-green-500 rounded-full"></span>
                Nueva línea de materiales
            </h2>
            <form method="POST" action="{{ route('produccion.bom.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
                    <div class="flex flex-col gap-1.5">
                        <label for="producto_id" class="text-xs font-bold text-slate-600 uppercase">Producto</label>
                        <select id="producto_id" name="producto_id" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all bg-white">
                            <option value="">Selecciona</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}" @selected((int) old('producto_id') === (int) $producto->id)>
                                    {{ $producto->nombre }} ({{ $producto->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2 col-span-full lg:col-span-1">
                        <button type="button" id="addBomRow" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-4 rounded-lg transition-all shadow-md active:scale-95">
                            + Agregar material
                        </button>
                    </div>
                </div>

                <div id="bomRows" class="space-y-4">
                    @php
                        $oldMaterials = old('material_id', []);
                        $oldCantidades = old('cantidad_base', []);
                        $oldMermas = old('merma_porcentaje', []);
                        $oldActivos = old('activo', []);
                        $rows = max(count($oldMaterials), 1);
                    @endphp

                    @for ($i = 0; $i < $rows; $i++)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 items-end bom-row">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-600 uppercase">Material</label>
                                <select name="material_id[]" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all bg-white">
                                    <option value="">Selecciona</option>
                                    @foreach ($materiales as $material)
                                        <option value="{{ $material->id }}" @selected((int) ($oldMaterials[$i] ?? null) === (int) $material->id)>
                                            {{ $material->nombre }} (stock: {{ number_format((float) $material->stock, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-600 uppercase text-nowrap">Cant. base por unidad</label>
                                <input name="cantidad_base[]" type="number" min="0.0001" step="0.0001" value="{{ $oldCantidades[$i] ?? '' }}" required 
                                    class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all" placeholder="0.0000">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-600 uppercase">Merma %</label>
                                <input name="merma_porcentaje[]" type="number" min="0" max="100" step="0.01" value="{{ $oldMermas[$i] ?? 0 }}" 
                                    class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-slate-600 uppercase">Activa</label>
                                <select name="activo[]" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all bg-white">
                                    <option value="1" @selected((string) ($oldActivos[$i] ?? '1') === '1')>Si</option>
                                    <option value="0" @selected((string) ($oldActivos[$i] ?? '1') === '0')>No</option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <button type="button" class="remove-bom-row w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-4 rounded-lg transition-all shadow-md active:scale-95">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                    <div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg transition-all shadow-md active:scale-95">
                            Guardar líneas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- Tabla de Recetas --}}
    <div class="mt-8 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center w-16">ID</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Producto</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">SKU</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Material</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Cantidad base</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500">Merma %</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">Activa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recetas as $linea)
                        <tr class="hover:bg-slate-50/50 transition-colors text-sm text-slate-700">
                            <td class="p-4 text-center font-mono text-xs text-slate-400">#{{ $linea->id }}</td>
                            <td class="p-4 font-bold text-slate-800">{{ $linea->producto?->nombre ?? '-' }}</td>
                            <td class="p-4 italic text-slate-500">{{ $linea->producto?->sku ?? '-' }}</td>
                            <td class="p-4">{{ $linea->material?->nombre ?? '-' }}</td>
                            <td class="p-4 font-medium">{{ number_format((float) $linea->cantidad_base, 4) }}</td>
                            <td class="p-4">
                                <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded text-xs font-bold">
                                    {{ number_format((float) $linea->merma_porcentaje, 2) }}%
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @if($linea->activo)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Si
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span> No
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500 italic">
                                Aún no hay líneas de materiales registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const bomRowsContainer = document.getElementById('bomRows');
    const addBomRowBtn = document.getElementById('addBomRow');

    function bindRemoveButton(row) {
        const btn = row.querySelector('.remove-bom-row');
        if (!btn) return;
        btn.addEventListener('click', () => {
            // Solo eliminar si hay más de una fila
            if (bomRowsContainer.querySelectorAll('.bom-row').length <= 1) {
                return;
            }
            row.remove();
        });
    }

    function createBomRow() {
        const templateRow = bomRowsContainer.querySelector('.bom-row');
        if (!templateRow) return null;

        const clone = templateRow.cloneNode(true);
        clone.querySelectorAll('select, input').forEach(input => {
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else if (input.type === 'number' || input.type === 'text') {
                input.value = '';
            }
        });

        bindRemoveButton(clone);
        return clone;
    }

    addBomRowBtn.addEventListener('click', () => {
        const newRow = createBomRow();
        if (newRow) {
            bomRowsContainer.appendChild(newRow);
        }
    });

    document.querySelectorAll('.bom-row').forEach(bindRemoveButton);
</script>
@endsection