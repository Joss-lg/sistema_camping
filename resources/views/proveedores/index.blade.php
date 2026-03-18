@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Encabezado Dinámico --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Proveedores</h1>
            <p class="text-slate-500 mt-2 max-w-2xl italic">
                Administra los proveedores de insumos para el flujo de fabricación de equipo de acampada.
            </p>
        </div>
        <a href="{{ route('proveedores.create') }}" 
           class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-green-200 active:scale-95 gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo proveedor
        </a>
    </div>

    {{-- Barra de Herramientas / Búsqueda --}}
    <section class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6">
        <form method="GET" action="{{ route('proveedores.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" 
                    placeholder="Buscar por nombre, contacto, correo..." 
                    class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">
                Buscar
            </button>
            @if($q)
                <a href="{{ route('proveedores.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2.5 rounded-xl font-bold text-sm transition-colors text-center">
                    Limpiar
                </a>
            @endif
        </form>
    </section>

    {{-- Tabla de Contenido --}}
    <section class="bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nombre / Empresa</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Contacto</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Info. Comunicación</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Estado</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proveedores as $proveedor)
                        @php
                            $estadoNombre = strtoupper((string) ($proveedor->estado->nombre ?? 'INACTIVO'));
                            $esActivo = $estadoNombre === 'ACTIVO';
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4 text-sm font-medium text-slate-400">#{{ $proveedor->id }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-800">{{ $proveedor->nombre }}</div>
                                <div class="text-[11px] text-slate-400 font-medium uppercase tracking-tighter">Proveedor Oficial</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 italic">
                                {{ $proveedor->contacto ?: 'Sin asignar' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 text-sm text-slate-600">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        {{ $proveedor->email ?: 'N/A' }}
                                    </span>
                                    <span class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        {{ $proveedor->telefono ?: 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $esActivo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $proveedor->estado->nombre ?? 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('proveedores.edit', $proveedor->id) }}" 
                                       class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>

                                    <form method="POST" action="{{ route('proveedores.toggle-estado', $proveedor->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                            class="p-2 rounded-lg transition-all shadow-sm {{ $esActivo ? 'bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-600 hover:text-white' }}"
                                            title="{{ $esActivo ? 'Desactivar' : 'Activar' }}">
                                            @if($esActivo)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="text-slate-400 font-medium">No se encontraron proveedores.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($proveedores->hasPages())
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                {{ $proveedores->links() }}
            </div>
        @endif
    </section>
</div>
@endsection