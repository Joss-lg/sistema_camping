@extends('layouts.app')

@section('content')
<div class="max-w-[600px] mx-auto mt-8 px-4">
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">
            Editar {{ $tipo === 'usuario' ? 'Usuario' : 'Proveedor' }}
        </h2>

        <form method="POST" action="#">
            @csrf
            
            {{-- Campos Informativos (Readonly) --}}
            <div class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre:</label>
                    <input type="text" value="{{ $registro->nombre }}" readonly 
                        class="bg-slate-50 border border-slate-200 text-slate-600 rounded-lg p-2.5 text-sm cursor-not-allowed outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Correo:</label>
                    <input type="email" value="{{ $registro->email }}" readonly 
                        class="bg-slate-50 border border-slate-200 text-slate-600 rounded-lg p-2.5 text-sm cursor-not-allowed outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rol:</label>
                    <input type="text" value="{{ $tipo === 'usuario' ? $registro->rol : 'PROVEEDOR' }}" readonly 
                        class="bg-slate-50 border border-slate-200 text-slate-600 rounded-lg p-2.5 text-sm cursor-not-allowed outline-none font-medium">
                </div>
            </div>

            @if ($tipo === 'usuario')
                <div class="mt-8 pt-6 border-t border-slate-100">
                    <strong class="text-slate-800 block mb-4">Permisos por módulo:</strong>
                    
                    <div class="space-y-3">
                        @foreach ($permisos as $permiso)
                            <div class="flex flex-wrap items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <span class="font-bold text-slate-700 text-sm w-full sm:w-auto mb-2 sm:mb-0">
                                    {{ $permiso->modulo }}
                                </span>
                                
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                        <input type="checkbox" name="modulos[]" value="{{ $permiso->modulo }}" {{ $permiso->puede_ver ? 'checked' : '' }}
                                            class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500">
                                        Puede ver
                                    </label>
                                    
                                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                        <input type="checkbox" name="puede_editar[]" value="{{ $permiso->modulo }}" {{ $permiso->puede_editar ? 'checked' : '' }}
                                            class="w-4 h-4 text-green-600 rounded border-slate-300 focus:ring-green-500">
                                        Puede editar
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <button type="submit" class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-lg transition-all shadow-md active:scale-[0.98]">
                        Guardar cambios
                    </button>
                </div>
            @else
                <div class="mt-8 p-4 bg-amber-50 border border-amber-100 rounded-lg text-amber-700 text-sm italic">
                    <div class="flex gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Los proveedores no tienen permisos editables desde este módulo.
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="w-full bg-slate-200 text-slate-500 font-bold py-2.5 rounded-lg cursor-not-allowed" disabled>
                        Guardar cambios
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection