@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Encabezado --}}
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Nuevo proveedor</h1>
        <p class="text-slate-500 mt-2 italic">
            Registra un proveedor para compras y abastecimiento de insumos operativos.
        </p>
    </div>

    {{-- Manejo de Errores --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm animate-pulse">
            <div class="flex items-center text-red-800 font-bold">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                Atención: {{ $errors->first() }}
            </div>
        </div>
    @endif

    {{-- Panel del Formulario --}}
    <section class="bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden max-w-4xl">
        <div class="bg-slate-50 border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-600 uppercase tracking-widest text-center sm:text-left">Información del Proveedor</h2>
        </div>

        <form method="POST" action="{{ route('proveedores.store') }}" class="p-6 sm:p-8 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nombre --}}
                <div class="flex flex-col gap-2">
                    <label for="nombre" class="text-xs font-bold text-slate-700 uppercase ml-1">Nombre de la Empresa</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all placeholder:text-slate-300"
                        placeholder="Ej: Acampada Libre S.A.">
                </div>

                {{-- Contacto --}}
                <div class="flex flex-col gap-2">
                    <label for="contacto" class="text-xs font-bold text-slate-700 uppercase ml-1">Persona de Contacto</label>
                    <input id="contacto" name="contacto" type="text" value="{{ old('contacto') }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all"
                        placeholder="Nombre del agente">
                </div>

                {{-- Email --}}
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-xs font-bold text-slate-700 uppercase ml-1">Correo Electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all"
                        placeholder="ventas@proveedor.com">
                </div>

                {{-- Teléfono --}}
                <div class="flex flex-col gap-2">
                    <label for="telefono" class="text-xs font-bold text-slate-700 uppercase ml-1">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono') }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all"
                        placeholder="+34 000 000 000">
                </div>

                {{-- Estado/Categoría --}}
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="estado_id" class="text-xs font-bold text-slate-700 uppercase ml-1">Estado de Relación</label>
                    <select id="estado_id" name="estado_id" required 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all bg-white cursor-pointer">
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" {{ (string) old('estado_id', $estados->first()->id ?? '') === (string) $estado->id ? 'selected' : '' }}>
                                {{ $estado->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Dirección --}}
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="direccion" class="text-xs font-bold text-slate-700 uppercase ml-1">Dirección Física</label>
                    <textarea id="direccion" name="direccion" rows="3" 
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all resize-none"
                        placeholder="Calle, Número, Ciudad y CP">{{ old('direccion') }}</textarea>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="tiempo_entrega_dias" class="text-xs font-bold text-slate-700 uppercase ml-1">Tiempo de Entrega (días)</label>
                    <input id="tiempo_entrega_dias" name="tiempo_entrega_dias" type="number" min="1" max="120" value="{{ old('tiempo_entrega_dias', 3) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="dias_credito" class="text-xs font-bold text-slate-700 uppercase ml-1">Crédito (días)</label>
                    <input id="dias_credito" name="dias_credito" type="number" min="0" max="365" value="{{ old('dias_credito', 0) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all">
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="condiciones_pago" class="text-xs font-bold text-slate-700 uppercase ml-1">Condiciones de Pago</label>
                    <input id="condiciones_pago" name="condiciones_pago" type="text" value="{{ old('condiciones_pago') }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all"
                        placeholder="Ej: Transferencia 50/50, neto 30 días">
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="calificacion" class="text-xs font-bold text-slate-700 uppercase ml-1">Calificación de Calidad (0 a 5)</label>
                    <input id="calificacion" name="calificacion" type="number" min="0" max="5" step="0.1" value="{{ old('calificacion', 0) }}"
                        class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none transition-all">
                </div>
            </div>

            {{-- Botones de Acción --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-100">
                <button type="submit" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-green-200 active:scale-95 flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Guardar proveedor
                </button>
                <a href="{{ route('proveedores.index') }}" 
                    class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-6 rounded-xl transition-all text-center active:scale-95 shadow-lg shadow-slate-200">
                    Volver al listado
                </a>
            </div>
        </form>
    </section>
</div>
@endsection