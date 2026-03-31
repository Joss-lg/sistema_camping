@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Editar orden de produccion</h1>
            <p class="text-slate-500 text-sm mt-1">Actualiza la informacion principal de la orden.</p>
        </div>
        <a href="{{ route('ordenes-produccion.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold">Volver</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <form method="POST" action="{{ route('ordenes-produccion.update', $ordenProduccion) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Numero de orden</label>
                    <input type="text" name="numero_orden" value="{{ old('numero_orden', $ordenProduccion->numero_orden) }}"
                        class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                    @error('numero_orden')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Tipo de producto</label>
                    <select name="tipo_producto_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                        @foreach($tiposProducto as $tipo)
                            <option value="{{ $tipo->id }}" @selected((int) old('tipo_producto_id', $ordenProduccion->tipo_producto_id) === (int) $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('ordenes-produccion.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-4 py-2 rounded-lg">Cancelar</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2 rounded-lg">Actualizar</button>
            </div>
        </form>
    </section>
</div>
@endsection
