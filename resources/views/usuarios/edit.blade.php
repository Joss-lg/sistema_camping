@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('usuarios.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold">
            ← Volver a Usuarios
        </a>
        <h1 class="text-3xl font-extrabold text-slate-800 mt-2">Editar Usuario</h1>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
            @csrf @method('PUT')

            {{-- Nombre --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 @error('name') border-red-500 @enderror">
                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 @error('email') border-red-500 @enderror">
                @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Nueva Contraseña (Opcional) --}}
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nueva Contraseña (Dejar en blanco para no cambiar)</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 @error('password') border-red-500 @enderror">
                    @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            {{-- Rol --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Rol</label>
                <select name="role_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500 @error('role_id') border-red-500 @enderror">
                    @foreach($roles as $rol)
                        <option value="{{ $rol->id }}" {{ old('role_id', $usuario->role_id) == $rol->id ? 'selected' : '' }}>
                            {{ $rol->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('role_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Teléfono --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Departamento --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Departamento</label>
                <input type="text" name="departamento" value="{{ old('departamento', $usuario->departamento) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Estado --}}
            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="activo" {{ old('activo', $usuario->activo) ? 'checked' : '' }} class="rounded">
                    <span class="text-sm font-semibold text-slate-700">Usuario Activo</span>
                </label>
            </div>

            {{-- Botones --}}
            <div class="flex gap-3 justify-end">
                <a href="{{ route('usuarios.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
