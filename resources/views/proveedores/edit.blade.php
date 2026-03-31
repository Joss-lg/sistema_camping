@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Encabezado --}}
    <div class="mb-8">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Editar proveedor</h1>
        </div>
        <p class="text-slate-500 mt-2 italic ml-11">
            Actualiza la información del proveedor <span class="text-slate-700 font-semibold">"{{ $proveedor->nombre }}"</span>.
        </p>
    </div>

    {{-- Manejo de Errores --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
            <div class="flex items-center text-red-800 font-bold">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                Atención: {{ $errors->first() }}
            </div>
        </div>
    @endif

    {{-- Panel del Formulario --}}
    <section class="bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden max-w-4xl">
        <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex justify-between items-center">
            <h2 class="text-sm font-bold text-slate-600 uppercase tracking-widest">Formulario de Edición</h2>
            <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold uppercase">ID: #{{ $proveedor->id }}</span>
        </div>

        <form method="POST" action="{{ route('proveedores.update', $proveedor->id) }}" class="p-6 sm:p-8 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nombre --}}
                <div class="flex flex-col gap-2">
                    <label for="nombre" class="text-xs font-bold text-slate-700 uppercase ml-1">Nombre de la Empresa</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $proveedor->nombre) }}" required 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                {{-- Contacto --}}
                <div class="flex flex-col gap-2">
                    <label for="contacto" class="text-xs font-bold text-slate-700 uppercase ml-1">Persona de Contacto</label>
                    <input id="contacto" name="contacto" type="text" value="{{ old('contacto', $proveedor->contacto) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                {{-- Email --}}
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-xs font-bold text-slate-700 uppercase ml-1">Correo Electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $proveedor->email) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                {{-- Teléfono --}}
                <div class="flex flex-col gap-2">
                    <label for="telefono" class="text-xs font-bold text-slate-700 uppercase ml-1">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $proveedor->telefono) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                {{-- Estado/Categoría --}}
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="estado_id" class="text-xs font-bold text-slate-700 uppercase ml-1">Estado de Relación</label>
                    <select id="estado_id" name="estado_id" required 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-white cursor-pointer">
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" {{ (string) old('estado_id', $proveedor->estado_id) === (string) $estado->id ? 'selected' : '' }}>
                                {{ $estado->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Dirección --}}
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="direccion" class="text-xs font-bold text-slate-700 uppercase ml-1">Dirección Física</label>
                    <textarea id="direccion" name="direccion" rows="3" 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all resize-none">{{ old('direccion', $proveedor->direccion) }}</textarea>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="tiempo_entrega_dias" class="text-xs font-bold text-slate-700 uppercase ml-1">Tiempo de Entrega (días)</label>
                    <input id="tiempo_entrega_dias" name="tiempo_entrega_dias" type="number" min="1" max="120" value="{{ old('tiempo_entrega_dias', $proveedor->tiempo_entrega_dias) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="dias_credito" class="text-xs font-bold text-slate-700 uppercase ml-1">Crédito (días)</label>
                    <input id="dias_credito" name="dias_credito" type="number" min="0" max="365" value="{{ old('dias_credito', $proveedor->dias_credito) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="condiciones_pago" class="text-xs font-bold text-slate-700 uppercase ml-1">Condiciones de Pago</label>
                    <input id="condiciones_pago" name="condiciones_pago" type="text" value="{{ old('condiciones_pago', $proveedor->condiciones_pago) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                        placeholder="Ej: Transferencia 50/50, neto 30 días">
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="calificacion" class="text-xs font-bold text-slate-700 uppercase ml-1">Calificación de Calidad (0 a 5)</label>
                    <input id="calificacion" name="calificacion" type="number" min="0" max="5" step="0.1" value="{{ old('calificacion', $proveedor->calificacion) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>
            </div>

            {{-- Botones de Acción --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-100">
                <button type="submit" 
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-blue-200 active:scale-95 flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Guardar cambios
                </button>
                <a href="{{ route('proveedores.index') }}" 
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-6 rounded-xl transition-all text-center active:scale-95">
                    Cancelar y volver
                </a>
            </div>
        </form>
    </section>
</div>
@endsection