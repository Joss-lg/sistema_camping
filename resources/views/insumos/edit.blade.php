@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Editar insumo</h1>
            <p class="text-slate-500 text-sm mt-1">Actualiza la información identificadora y estado del insumo.</p>
        </div>
        <a href="{{ route('insumos.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-semibold transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver
        </a>
    </div>

    @include('partials.flash-messages')

    <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
        <div class="mb-6 pb-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-800">Datos del insumo</h2>
            <p class="text-slate-500 text-sm mt-1">Completa la información requerida para el insumo.</p>
        </div>

        <form method="POST" action="{{ route('insumos.update', $insumo) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="codigo_insumo" class="text-sm font-semibold text-slate-700">Código de insumo</label>
                    <input id="codigo_insumo" type="text" name="codigo_insumo" value="{{ old('codigo_insumo', $insumo->codigo_insumo) }}" placeholder="Ej: INS-001" required class="border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring">
                    @error('codigo_insumo')
                        <p class="text-red-600 text-xs flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.417-1.412l-5.955 6.286L7.651 10.9a1 1 0 00-1.417 1.412l4.083 4.084a1 1 0 001.417 0l7.378-7.805z" clip-rule="evenodd"></path></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div class="flex flex-col gap-2">
                    <label for="nombre" class="text-sm font-semibold text-slate-700">Nombre del insumo</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $insumo->nombre) }}" placeholder="Ej: Acero tipo X" required class="border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-ring">
                    @error('nombre')
                        <p class="text-red-600 text-xs flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.417-1.412l-5.955 6.286L7.651 10.9a1 1 0 00-1.417 1.412l4.083 4.084a1 1 0 001.417 0l7.378-7.805z" clip-rule="evenodd"></path></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('insumos.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition-colors">Cancelar</a>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm">
                    <span class="flex items-center gap-2 justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Actualizar insumo
                    </span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
