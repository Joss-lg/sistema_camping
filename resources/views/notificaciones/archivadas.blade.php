@extends('layouts.app')

@section('content')
<div class="lc-shell">
    <div class="lc-header mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Notificaciones del Sistema</h1>
        <p class="mt-1 text-sm text-slate-600">Gestiona tus alertas y avisos del sistema</p>
    </div>

    <!-- Tabs para cambiar entre Activas y Archivadas -->
    <div class="mb-6 border-b border-slate-200">
        <div class="flex gap-4">
            <a href="{{ route('notificaciones.index') }}" class="pb-3 px-1 font-semibold text-slate-500 hover:text-slate-700 border-b-2 border-transparent hover:border-slate-300">
                📬 Activas
            </a>
            <a href="{{ route('notificaciones.archivadas') }}" class="pb-3 px-1 font-semibold text-slate-900 border-b-2 border-emerald-500">
                📦 Archivadas
            </a>
        </div>
    </div>

    @if ($notificaciones->count() > 0)
        <div class="space-y-3">
            @foreach ($notificaciones as $notif)
                <div class="lc-card bg-slate-100 p-4 rounded-lg shadow-sm opacity-75">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-slate-700">{{ $notif->titulo }}</h3>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $notif->prioridad === 'Alta' ? 'bg-red-100 text-red-800' : ($notif->prioridad === 'Media' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $notif->prioridad }}
                                </span>
                                <span class="text-xs bg-slate-300 text-slate-600 px-2 py-0.5 rounded">{{ $notif->modulo }}</span>
                            </div>
                            <p class="text-sm text-slate-600 mb-2">{{ $notif->mensaje }}</p>
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span>{{ $notif->created_at->format('d/m/Y H:i') }}</span>
                                <span class="text-slate-400">📦 Archivada</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('notificaciones.restaurar', $notif->id) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-100 rounded-md hover:bg-emerald-200 transition-colors">
                                    ↻ Restaurar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notificaciones->links() }}
        </div>
    @else
        <div class="lc-card p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-slate-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0A8.967 8.967 0 0 1 6 9.75a6 6 0 1 1 12 0 8.967 8.967 0 0 1-3.143 7.332ZM9 17.25h6a3 3 0 1 1-6 0Z" />
            </svg>
            <h3 class="mt-3 font-semibold text-slate-900">Sin notificaciones archivadas</h3>
            <p class="mt-1 text-sm text-slate-600">No tienes notificaciones archivadas en este momento</p>
        </div>
    @endif
</div>
@endsection
