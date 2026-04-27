<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LogiCamp' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
@php
    $isFullPage = isset($hideSidebar) && $hideSidebar;
    $authUser = auth()->user();
    $roleKey = \App\Services\PermisoService::normalizeRoleKey((string) ($authUser?->role?->slug ?: $authUser?->role?->nombre));
    $roleLabel = $authUser?->role?->nombre
        ?: ($roleKey ? str_replace('_', ' ', ucwords(strtolower($roleKey), '_')) : 'Sin rol');
    $isSuperAdmin = \App\Services\PermisoService::isSuperAdmin($authUser);
    $isProveedor = $roleKey === 'PROVEEDOR';
    $canAccess = static fn (string $module, string $action = 'ver'): bool => $authUser
        ? ($isSuperAdmin || $authUser->canCustom($module, $action))
        : false;

    $latestNotifications = $authUser
        ? \App\Models\NotificacionSistema::query()
            ->paraUsuario($authUser)
            ->noArchivadas()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
        : collect();

    $pendingNotificationsCount = $authUser
        ? \App\Models\NotificacionSistema::query()
            ->paraUsuario($authUser)
            ->where('estado', 'Pendiente')
            ->count()
        : 0;

    $groupProveedorOpen = request()->routeIs('entregas.*');
    $groupAbastecimientoOpen = request()->routeIs('proveedores.*')
        || request()->routeIs('ordenes-compra.*')
        || request()->routeIs('insumos.*')
        || request()->routeIs('almacenes.*')
        || request()->routeIs('produccion.bom.*');
    $groupOperacionesOpen = request()->routeIs('dashboard')
        || request()->routeIs('ordenes-produccion.*')
        || request()->routeIs('produccion')
        || request()->routeIs('produccion.index')
        || request()->routeIs('trazabilidad.*');
    $groupInventarioOpen = request()->routeIs('terminados.*') || request()->routeIs('entregas.*');
    $groupAdministracionOpen = request()->routeIs('reportes.*') || request()->routeIs('permisos.*');
@endphp
<body class="{{ $isFullPage ? 'min-h-screen text-slate-900 font-sans antialiased' : 'text-slate-900 font-sans antialiased' }}">

    <div
        class="{{ $isFullPage ? 'min-h-screen' : 'min-h-screen flex' }}"
        x-data="{
            sidebarOpen: true,
            init() {
                this.sidebarOpen = localStorage.getItem('logicamp-sidebar-open') !== 'false';
                this.$watch('sidebarOpen', (value) => localStorage.setItem('logicamp-sidebar-open', value ? 'true' : 'false'));
            },
            closeSidebar() {
                this.sidebarOpen = false;
            },
            openSidebar() {
                this.sidebarOpen = true;
            },
            scrollToTop() {
                const main = document.querySelector('main');
                if (main) { main.scrollTo({ top: 0, behavior: 'smooth' }); }
            }
        }"
        x-on:keydown.escape.window="closeSidebar()"
    >

        @if(! $isFullPage)
        <aside
            x-cloak
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-3"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-3"
            class="sticky top-0 h-screen w-[258px] shrink-0 overflow-hidden border-r border-slate-800/80 bg-[#0f172a] px-5 pb-5 pt-6 text-white shadow-[18px_0_40px_-30px_rgba(15,23,42,0.85)] flex flex-col"
        >
            <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-300/80">Operacion</div>
                    <div class="mt-1 text-xl font-bold tracking-tight text-white">LogiCamp</div>
                </div>
                <button
                    type="button"
                    x-on:click="closeSidebar()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700/80 bg-slate-900/60 text-slate-300 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-400"
                    aria-label="Cerrar sidebar"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav x-on:click="if ($event.target.closest('a')) closeSidebar()" class="lc-scrollbar mt-1 flex-1 space-y-1.5 overflow-y-auto pr-1 pb-2 min-h-0">
                @if ($isProveedor)
                <div class="mt-2 mb-1">
                    @if ($canAccess('Entregas'))
                    <a href="{{ route('entregas.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h7.5m-10.5 0H3.75m15 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h1.5m-16.5-3h15.75a1.5 1.5 0 0 0 1.5-1.5v-4.5a1.5 1.5 0 0 0-1.5-1.5h-1.636a1.5 1.5 0 0 1-1.061-.439L14.25 5.69a1.5 1.5 0 0 0-1.06-.44H3.75" />
                        </svg>
                        <span>Mis entregas</span>
                    </a>
                    @endif
                </div>
                @else
                @if ($canAccess('Proveedores') || $canAccess('Compras') || $canAccess('Insumos') || $canAccess('Produccion'))
                <div class="mt-2 mb-1">
                    <div class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Abastecimiento y Planeación
                    </div>
                @endif
                @if ($canAccess('Proveedores'))
                <a href="{{ route('proveedores.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('proveedores.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h7.5m-10.5 0H3.75m15 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h1.5m-16.5-3h15.75a1.5 1.5 0 0 0 1.5-1.5v-4.5a1.5 1.5 0 0 0-1.5-1.5h-1.636a1.5 1.5 0 0 1-1.061-.439L14.25 5.69a1.5 1.5 0 0 0-1.06-.44H3.75" />
                    </svg>
                    <span>Proveedores</span>
                </a>
                @endif
                @if ($canAccess('Insumos'))
                <a href="{{ route('almacenes.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('almacenes.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 21V8.25l3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21" />
                    </svg>
                    <span>Almacenes</span>
                </a>
                @endif
                @if ($canAccess('Insumos'))
                <a href="{{ route('insumos.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('insumos.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 6 1.5-1.5m0 0L13.5 6M12 4.5V9m4.5 6 1.5 1.5m0 0L19.5 15M18 16.5V12M6 12H3m3 0 1.5-1.5M6 12l1.5 1.5m8.379-8.379 1.06-1.06a2.121 2.121 0 1 1 3 3l-1.06 1.06m-2.12-2.12L7.5 15.27V18h2.73l9.258-9.258m-2.12-2.121 2.12 2.12" />
                    </svg>
                    <span>Insumos</span>
                </a>
                @endif
                
                @if ($canAccess('Compras'))
                <a href="{{ route('ordenes-compra.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('ordenes-compra.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386a.75.75 0 0 1 .727.568l.651 2.605m0 0 1.54 6.161a2.25 2.25 0 0 0 2.183 1.703h7.632a2.25 2.25 0 0 0 2.183-1.703l1.154-4.616a.75.75 0 0 0-.727-.932H5.014Zm3.736 11.077a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Zm8.25 0a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Z" />
                    </svg>
                    <span>Órdenes de Compras</span>
                </a>
                @endif

                @if ($canAccess('Produccion'))
                <a href="{{ route('produccion.bom.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('produccion.bom.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5M3.75 7.5h16.5m-16.5 9h16.5M5.25 3.75h13.5A1.5 1.5 0 0 1 20.25 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z" />
                    </svg>
                    <span>Recetas y BOM</span>
                </a>
                @endif
                @if ($canAccess('Proveedores') || $canAccess('Compras') || $canAccess('Insumos') || $canAccess('Produccion'))
                </div>
                @endif

                @if ($canAccess('Dashboard') || $canAccess('Produccion') || $canAccess('Trazabilidad'))
                <div class="mt-4 mb-1">
                    <div class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Operaciones (Día a Día)
                    </div>
                @endif
                @if ($canAccess('Dashboard'))
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v18h18M7.5 14.25h2.25V18H7.5v-3.75Zm4.5-5.25h2.25V18H12V9Zm4.5 2.25h2.25V18H16.5v-6.75Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                @endif
                @if ($canAccess('Produccion'))
                    <a href="{{ route('produccion.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ (request()->routeIs('produccion.index') || request()->routeIs('produccion')) ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 2.557a1.5 1.5 0 0 1 1.16 0l.758.307a1.5 1.5 0 0 0 1.483-.197l.664-.493a1.5 1.5 0 0 1 1.556-.148l1.09.63a1.5 1.5 0 0 1 .723 1.385l-.047.827a1.5 1.5 0 0 0 .615 1.364l.666.493a1.5 1.5 0 0 1 .543 1.465l-.19 1.246a1.5 1.5 0 0 0 .26 1.133l.493.666a1.5 1.5 0 0 1 .148 1.556l-.63 1.09a1.5 1.5 0 0 1-1.385.723l-.827-.047a1.5 1.5 0 0 0-1.364.615l-.493.666a1.5 1.5 0 0 1-1.465.543l-1.246-.19a1.5 1.5 0 0 0-1.133.26l-.666.493a1.5 1.5 0 0 1-1.556.148l-1.09-.63a1.5 1.5 0 0 1-.723-1.385l.047-.827a1.5 1.5 0 0 0-.615-1.364l-.666-.493a1.5 1.5 0 0 1-.543-1.465l.19-1.246a1.5 1.5 0 0 0-.26-1.133l-.493-.666a1.5 1.5 0 0 1-.148-1.556l.63-1.09a1.5 1.5 0 0 1 1.385-.723l.827.047a1.5 1.5 0 0 0 1.364-.615l.493-.666a1.5 1.5 0 0 1 1.465-.543l1.246.19a1.5 1.5 0 0 0 1.133-.26l.666-.493Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Produccion</span>
                </a>
                <a href="{{ route('ordenes-produccion.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('ordenes-produccion.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 21V8.25l3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21" />
                    </svg>
                    <span>Ordenes de Produccion</span>
                </a>

                @endif
                @if ($canAccess('Trazabilidad'))
                <a href="{{ route('trazabilidad.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('trazabilidad.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v18h16.5M7.5 15.75 10.5 12l3 2.25 4.5-6" />
                    </svg>
                    <span>Trazabilidad</span>
                </a>
                @endif
                @if ($canAccess('Dashboard') || $canAccess('Produccion') || $canAccess('Trazabilidad'))
                </div>
                @endif

                @if ($canAccess('Terminados') || $canAccess('Entregas'))
                <div class="mt-4 mb-1">
                    <div class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Inventario y Salidas
                    </div>
                @endif
                @if ($canAccess('Terminados'))
                <a href="{{ route('terminados.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('terminados.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Terminados</span>
                </a>
                @endif
                @if ($canAccess('Entregas'))
                <a href="{{ route('entregas.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h7.5m-10.5 0H3.75m15 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0h1.5m-16.5-3h15.75a1.5 1.5 0 0 0 1.5-1.5v-4.5a1.5 1.5 0 0 0-1.5-1.5h-1.636a1.5 1.5 0 0 1-1.061-.439L14.25 5.69a1.5 1.5 0 0 0-1.06-.44H3.75" />
                    </svg>
                    <span>Entregas</span>
                </a>
                @endif
                @if ($canAccess('Terminados') || $canAccess('Entregas'))
                </div>
                @endif

                @if ($canAccess('Reportes') || $canAccess('Permisos'))
                <div class="mt-4 mb-1">
                    <div class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Administración
                    </div>
                @endif
                @if ($canAccess('Reportes'))
                <a href="{{ route('reportes.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('reportes.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v18h16.5M7.5 14.25h2.25V18H7.5v-3.75Zm4.5-5.25h2.25V18H12V9Zm4.5 2.25h2.25V18H16.5v-6.75Z" />
                    </svg>
                    <span>Reportes operativos</span>
                </a>
                @endif
                @if ($canAccess('Permisos'))
                <a href="{{ route('permisos.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('permisos.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5A1.5 1.5 0 0 1 18.75 12v7.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V12a1.5 1.5 0 0 1 1.5-1.5Z" />
                    </svg>
                    <span>Permisos y Usuarios</span>   
                </a>
                @endif
                <a href="{{ route('notificaciones.index') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-md text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors {{ request()->routeIs('notificaciones.*') ? 'bg-emerald-900/30 text-emerald-400' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0A8.967 8.967 0 0 1 6 9.75a6 6 0 1 1 12 0 8.967 8.967 0 0 1-3.143 7.332ZM9 17.25h6a3 3 0 1 1-6 0Z" />
                    </svg>
                    <span>Notificaciones</span>
                    @if ($pendingNotificationsCount > 0)
                        <span class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-[11px] font-bold text-white">
                            {{ $pendingNotificationsCount > 99 ? '99+' : $pendingNotificationsCount }}
                        </span>
                    @endif
                </a>
                @if ($canAccess('Reportes') || $canAccess('Permisos'))
                </div>
                @endif
                @endif
            </nav>

            <div class="mt-4 w-full border-t border-slate-800/90 bg-[#0f172a] pt-4 shrink-0">
                <div class="rounded-2xl border border-slate-700/80 bg-slate-900/70 p-3.5 text-sm text-slate-200 shadow-inner shadow-black/20">
                <div class="flex items-start gap-3 mb-3">
                    <div class="h-9 w-9 rounded-full bg-emerald-900/40 text-emerald-300 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-white truncate">{{ $authUser?->name ?: 'Usuario' }}</div>
                        <div class="mt-1 inline-flex items-center rounded-md border border-emerald-800/60 bg-emerald-900/30 px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-emerald-300">
                            {{ $roleLabel }}
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-red-600 py-2.5 text-white font-semibold transition-colors hover:bg-red-700 active:scale-[0.99]">
                        Cerrar sesión
                    </button>
                </form>
                </div>
            </div>

        </aside>
        @endif

        <main class="{{ $isFullPage ? '' : 'min-h-screen w-full overflow-y-auto p-4 lg:p-6' }}">
            @if (! $isFullPage)
            <div
                x-cloak
                x-show="!sidebarOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mb-5 flex"
            >
                <button
                    type="button"
                    x-on:click="openSidebar()"
                    x-ref="navToggleButton"
                    :aria-expanded="sidebarOpen.toString()"
                    class="inline-flex items-center gap-3 rounded-2xl border border-emerald-700 bg-emerald-600 px-3.5 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-900/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
                    aria-label="Abrir sidebar"
                >
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-inset ring-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h10.5" />
                        </svg>
                    </span>
                    <span class="flex flex-col items-start leading-none">
                        <span class="text-[10px] font-medium uppercase tracking-[0.24em] text-emerald-100/90">Navegacion</span>
                        <span class="mt-1 text-sm font-semibold text-white">Abrir menu</span>
                    </span>
                </button>
            </div>
            <div class="lc-shell min-h-[calc(100vh-3rem)] p-5 lg:p-6">
                <div class="mb-5 flex items-center justify-end gap-3">
                    <div
                        x-data="notificationBell({
                            summaryUrl: '{{ route('notificaciones.resumen') }}',
                            pendingCount: {{ $pendingNotificationsCount }},
                            items: @js($latestNotifications->map(fn ($notificationItem) => [
                                'id' => $notificationItem->id,
                                'titulo' => (string) $notificationItem->titulo,
                                'mensaje' => (string) $notificationItem->mensaje,
                                'estado' => (string) $notificationItem->estado,
                                'url' => $notificationItem->url_accion ? url((string) $notificationItem->url_accion) : route('notificaciones.index'),
                                'created_at' => optional($notificationItem->created_at)?->format('d/m/Y H:i'),
                            ])->values()),
                            pollMs: 15000,
                        })"
                        x-init="init()"
                        class="relative"
                    >
                        <button
                            type="button"
                            x-on:click="toggle()"
                            x-on:click.outside="open = false"
                            aria-label="Notificaciones ({{ $pendingNotificationsCount }} pendientes)"
                            :aria-label="'Notificaciones (' + pendingCount + ' pendientes)'"
                            class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-emerald-300 hover:text-emerald-600"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0A8.967 8.967 0 0 1 6 9.75a6 6 0 1 1 12 0 8.967 8.967 0 0 1-3.143 7.332ZM9 17.25h6a3 3 0 1 1-6 0Z" />
                            </svg>
                            <span
                                x-cloak
                                x-show="pendingCount > 0"
                                x-text="pendingCount > 99 ? '99+' : pendingCount"
                                class="absolute -right-1 -top-1 inline-flex min-w-6 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[11px] font-bold text-white ring-2 ring-white"
                            >{{ $pendingNotificationsCount > 99 ? '99+' : $pendingNotificationsCount }}</span>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            x-transition
                            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Notificaciones</p>
                                    <p class="text-xs text-slate-500" x-text="pendingCount + ' pendiente(s)'"></p>
                                </div>
                                <a href="{{ route('notificaciones.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Ver todas</a>
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                <template x-if="items.length === 0">
                                    <div class="px-4 py-6 text-center text-sm text-slate-500">
                                        No tienes notificaciones por ahora.
                                    </div>
                                </template>

                                <template x-for="item in items" :key="item.id">
                                    <a :href="item.url" class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800" x-text="item.titulo"></p>
                                                <p class="mt-1 text-xs text-slate-600 line-clamp-2" x-text="item.mensaje"></p>
                                            </div>
                                            <span x-show="item.estado === 'Pendiente'" class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                        </div>
                                        <p class="mt-2 text-[11px] text-slate-400" x-text="item.created_at"></p>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if (session('ok'))
                    <div class="lc-alert lc-alert-success mb-4 border border-green-200 bg-green-50 text-sm text-green-800">
                        {{ session('ok') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="lc-alert lc-alert-danger mb-4 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>

            @else
                @yield('content')
            @endif
        </main>
    </div>


    @livewireScripts
    @if(! (isset($hideSidebar) && $hideSidebar))
    <button
        type="button"
        onclick="var m=document.querySelector('main');if(m){m.scrollTop=0;}window.scrollTo({top:0,behavior:'smooth'});"
        style="position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;width:3rem;height:3rem;border-radius:9999px;background-color:#059669;border:2px solid #047857;color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 10px 15px -3px rgba(0,0,0,.2);cursor:pointer;"
        aria-label="Volver arriba"
        title="Volver arriba"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:1.25rem;height:1.25rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
        </svg>
    </button>
    @endif
</body>
</html>