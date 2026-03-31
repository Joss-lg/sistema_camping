@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Crear orden de compra</h1>
            <p class="text-slate-500 text-sm mt-1">Registra una nueva orden seleccionando el proveedor.</p>
        </div>
        <a href="{{ route('ordenes-compra.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold">Volver</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <form method="POST" action="{{ route('ordenes-compra.store') }}" class="space-y-5">
            @csrf
            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Proveedor</label>
                <select name="proveedor_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                    <option value="">Seleccione proveedor</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected((string) old('proveedor_id') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                    @endforeach
                </select>
                @error('proveedor_id')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Fecha de entrega prevista <span class="text-red-600">*</span></label>
                <input type="date" name="fecha_entrega_prevista" value="{{ old('fecha_entrega_prevista') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                @error('fecha_entrega_prevista')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Notas</label>
                <textarea name="notas" rows="2" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">{{ old('notas') }}</textarea>
                @error('notas')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Impuestos</label>
                <input type="number" step="0.01" name="impuestos" value="{{ old('impuestos') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                @error('impuestos')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Descuentos</label>
                <input type="number" step="0.01" name="descuentos" value="{{ old('descuentos') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                @error('descuentos')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Costo de flete</label>
                <input type="number" step="0.01" name="costo_flete" value="{{ old('costo_flete') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                @error('costo_flete')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Condiciones de pago</label>
                <input type="text" name="condiciones_pago" value="{{ old('condiciones_pago') }}" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                @error('condiciones_pago')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>


            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Detalles de insumos <span class="text-red-600">*</span></label>
                <table class="w-full text-sm border border-slate-300 rounded-lg mb-2">
                    <thead class="bg-slate-100">
                        <tr>
                            <th>Insumo</th>
                            <th>Unidad</th>
                            <th>Cantidad</th>
                            <th>Precio unitario</th>
                            <th>Descuento %</th>
                            <th>Fecha entrega línea</th>
                            <th>Notas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detalles-body">
                        <!-- Filas dinámicas JS -->
                    </tbody>
                </table>
                <button type="button" onclick="agregarDetalle()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1 rounded-lg">Agregar insumo</button>
                @error('detalles')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <script>
                let insumos = @json($insumos);
                let unidades = @json($unidades);
                function agregarDetalle() {
                    const tbody = document.getElementById('detalles-body');
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <select name="detalles[][insumo_id]" class="border border-slate-300 rounded-lg p-1">
                                ${insumos.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('')}
                            </select>
                        </td>
                        <td>
                            <select name="detalles[][unidad_medida_id]" class="border border-slate-300 rounded-lg p-1">
                                ${unidades.map(u => `<option value="${u.id}">${u.nombre}</option>`).join('')}
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="detalles[][cantidad_solicitada]" class="border border-slate-300 rounded-lg p-1" required></td>
                        <td><input type="number" step="0.01" name="detalles[][precio_unitario]" class="border border-slate-300 rounded-lg p-1" required></td>
                        <td><input type="number" step="0.01" name="detalles[][descuento_porcentaje]" class="border border-slate-300 rounded-lg p-1"></td>
                        <td><input type="date" name="detalles[][fecha_entrega_esperada_linea]" class="border border-slate-300 rounded-lg p-1"></td>
                        <td><input type="text" name="detalles[][notas]" class="border border-slate-300 rounded-lg p-1"></td>
                        <td><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 font-bold">X</button></td>
                    `;
                    tbody.appendChild(row);
                }
            </script>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('ordenes-compra.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-4 py-2 rounded-lg">Cancelar</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2 rounded-lg">Guardar</button>
            </div>
        </form>
    </section>
</div>
@endsection
