<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LogiCamp' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
@endphp
<body class="{{ $isFullPage ? 'min-h-screen text-slate-900 font-sans antialiased' : 'bg-slate-50 text-slate-900 font-sans antialiased' }}">

    <div class="{{ $isFullPage ? 'min-h-screen' : 'min-h-screen grid grid-cols-1 lg:grid-cols-[250px_1fr]' }}">

        @if(! $isFullPage)
        <aside class="bg-[#0f172a] text-white p-5 lg:sticky lg:top-0 lg:h-screen overflow-y-auto flex flex-col">

            <div class="text-xl font-bold mb-5 tracking-tight">LogiCamp</div>

            <nav class="flex-grow grid grid-cols-2 gap-1.5 lg:block lg:space-y-1.5">
                @if ($isProveedor)
                <div class="col-span-2 mt-2 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest lg:mt-4">
                    📦 Portal de Proveedor
                </div>
                @if ($canAccess('Entregas'))
                <a href="{{ route('entregas.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-green-600/20 text-white' : '' }}">📦 Mis entregas</a>
                @endif
                @else
                <div class="col-span-2 mt-2 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest lg:mt-4">
                    🏠 Operaciones (El día a día)
                </div>
                @if ($canAccess('Dashboard'))
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-600/20 text-white' : '' }}">📊 Dashboard</a>
                @endif
                @if ($canAccess('Produccion'))
                <a href="{{ route('ordenes-produccion.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('ordenes-produccion.*') ? 'bg-green-600/20 text-white' : '' }}">🏭 Órdenes de Producción</a>
                <a href="{{ route('produccion.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ (request()->routeIs('produccion.index') || request()->routeIs('produccion') ) ? 'bg-green-600/20 text-white' : '' }}">⚙️ Producción</a>
                <a href="{{ route('produccion.bom.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('produccion.bom.*') ? 'bg-green-600/20 text-white' : '' }}">📖 Recetas y BOM</a>
                @endif
                @if ($canAccess('Trazabilidad'))
                <a href="{{ route('trazabilidad.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('trazabilidad.*') ? 'bg-green-600/20 text-white' : '' }}">📈 Trazabilidad</a>
                @endif
                @if ($canAccess('Insumos'))
                <a href="{{ route('insumos.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('insumos.*') ? 'bg-green-600/20 text-white' : '' }}">🔧 Insumos</a>
                @endif
                @if ($canAccess('Entregas'))
                <a href="{{ route('entregas.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-green-600/20 text-white' : '' }}">📦 Entregas</a>
                @endif
                @if ($canAccess('Terminados'))
                <a href="{{ route('terminados.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('terminados.*') ? 'bg-green-600/20 text-white' : '' }}">✅ Terminados</a>
                @endif

                @if ($canAccess('Compras') || $canAccess('Proveedores') || $canAccess('Permisos'))
                <div class="col-span-2 mt-4 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest">
                    📋 Planeación y Configuración
                </div>
                @endif
                @if ($canAccess('Compras'))
                <a href="{{ route('ordenes-compra.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('ordenes-compra.*') ? 'bg-green-600/20 text-white' : '' }}">🛍️ Compras</a>
                @endif
                @if ($canAccess('Proveedores'))
                <a href="{{ route('proveedores.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('proveedores.*') ? 'bg-green-600/20 text-white' : '' }}">🚚 Proveedores</a>
                @endif
                @if ($canAccess('Permisos'))
                <a href="{{ route('permisos.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('permisos.*') ? 'bg-green-600/20 text-white' : '' }}">🔐 Permisos</a>
                @endif

                @if ($canAccess('Reportes'))
                <div class="col-span-2 mt-4 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest">
                    📊 Inteligencia y Control
                </div>
                <a href="{{ route('reportes.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('reportes.*') ? 'bg-green-600/20 text-white' : '' }}">📈 Reportes operativos</a>
                @endif
                @endif
            </nav>

            <div class="mt-8 p-3 border border-white/20 rounded-xl text-sm text-slate-200">
                <div class="font-bold text-white mb-0.5">{{ $authUser?->name ?: 'Usuario' }}</div>
                <div class="text-xs text-slate-400 mb-3">Rol: {{ $roleLabel }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-lg transition-colors shadow-sm active:scale-95">
                        Cerrar sesión
                    </button>
                </form>
            </div>

        </aside>
        @endif

        <main class="{{ $isFullPage ? '' : 'p-6' }}">
            @if (! $isFullPage)
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm min-h-full">
                
                @if (session('ok'))
                    <div class="mb-4 p-3 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">
                        {{ session('ok') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 p-3 rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm">
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

</body>
</html>