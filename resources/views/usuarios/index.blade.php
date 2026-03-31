@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800">Gestión de Usuarios</h1>
                <p class="text-slate-500 mt-1">Administra los usuarios del sistema</p>
            </div>
            <a href="{{ route('usuarios.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                + Nuevo Usuario
            </a>
        </div>
    </div>

    {{-- Mensajes de sesión --}}
    @if($message = session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            {{ $message }}
        </div>
    @endif

    {{-- Búsqueda --}}
    <div class="mb-6 bg-white p-4 rounded-lg border border-slate-200">
        <form method="GET" action="{{ route('usuarios.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o email..." class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500">
            <button type="submit" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                Buscar
            </button>
        </form>
    </div>

    {{-- Tabla de Usuarios --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Nombre</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Rol</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Departamento</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Estado</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($usuarios as $usuario)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $usuario->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $usuario->email }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-semibold">
                                {{ $usuario->role?->nombre ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $usuario->departamento ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <form method="POST" action="{{ route('usuarios.toggle-activo', $usuario) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2 py-1 text-xs font-semibold rounded transition {{ $usuario->activo ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex gap-2">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('¿Confirmar eliminación?')" class="text-red-600 hover:text-red-800 font-semibold">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                            No hay usuarios registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-6">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection
