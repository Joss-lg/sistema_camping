@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Editar orden de compra</h1>
            <p class="text-slate-500 text-sm mt-1">Actualiza el proveedor asociado a la orden.</p>
        </div>
        <a href="{{ route('ordenes-compra.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold">Volver</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <form method="POST" action="{{ route('ordenes-compra.update', $ordenCompra) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-1.5 max-w-xl">
                <label class="text-sm font-semibold text-slate-600">Proveedor</label>
                <select name="proveedor_id" class="border border-slate-300 rounded-lg p-2.5 text-sm bg-white focus:ring-2 focus:ring-green-500 outline-none">
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $ordenCompra->proveedor_id) === (int) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('ordenes-compra.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-4 py-2 rounded-lg">Cancelar</a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2 rounded-lg">Actualizar</button>
            </div>
        </form>
    </section>
</div>
@endsection
