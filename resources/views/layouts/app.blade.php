<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LogiCamp' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased">

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-[250px_1fr]">
        
        <aside class="bg-[#0f172a] text-white p-5 lg:sticky lg:top-0 lg:h-screen overflow-y-auto flex flex-col">
            @php
                $sessionRole = strtoupper((string) session('auth_user_rol', ''));
                $sessionUserId = (int) session('auth_user_id', 0);
                $modulePermissions = $sessionUserId > 0
                    ? \App\Models\UsuarioPermiso::where('usuario_id', $sessionUserId)->get(['modulo', 'puede_ver', 'puede_editar'])
                    : collect();

                $legacyViewAccess = function (string $modulo) use ($sessionRole): bool {
                    if ($sessionRole === 'ADMIN') return true;
                    if ($sessionRole === 'ALMACEN') {
                        return in_array($modulo, ['Dashboard', 'Compras', 'Insumos', 'Produccion', 'Terminados', 'Trazabilidad', 'Reportes'], true);
                    }
                    if ($sessionRole === 'PROVEEDOR') return $modulo === 'Compras';
                    return false;
                };

                $canViewModule = function (string $modulo) use ($modulePermissions, $legacyViewAccess): bool {
                    if ($modulePermissions->isEmpty()) return $legacyViewAccess($modulo);
                    $permission = $modulePermissions->firstWhere('modulo', $modulo);
                    return $permission ? ((bool) $permission->puede_ver || (bool) $permission->puede_editar) : false;
                };
            @endphp

            <div class="text-xl font-bold mb-5 tracking-tight">LogiCamp</div>

            <nav class="flex-grow grid grid-cols-2 gap-1.5 lg:block lg:space-y-1.5">
                
                <div class="col-span-2 mt-2 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest lg:mt-4">
                    Inicio
                </div>
                @if ($canViewModule('Dashboard'))
                    <a href="{{ route('dashboard') }}" 
                       class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-600/20 text-white' : '' }}">
                        Dashboard
                    </a>
                @endif

                <div class="col-span-2 mt-4 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest">
                    Ruta principal
                </div>
                @if ($canViewModule('Produccion'))
                    <a href="{{ route('produccion.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('produccion.index') ? 'bg-green-600/20 text-white' : '' }}">1. Produccion</a>
                    <a href="{{ route('produccion.bom.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('produccion.bom.*') ? 'bg-green-600/20 text-white' : '' }}">1.1 Ordenes y receta</a>
                @endif
                @if ($canViewModule('Terminados'))
                    <a href="{{ route('terminados.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('terminados.*') ? 'bg-green-600/20 text-white' : '' }}">2. Terminados</a>
                @endif
                @if ($canViewModule('Trazabilidad'))
                    <a href="{{ route('trazabilidad.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('trazabilidad.*') ? 'bg-green-600/20 text-white' : '' }}">3. Trazabilidad</a>
                @endif
                @if ($canViewModule('Reportes'))
                    <a href="{{ route('reportes.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('reportes.*') ? 'bg-green-600/20 text-white' : '' }}">4. Reportes</a>
                @endif

                <div class="col-span-2 mt-4 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest">
                    Soporte operativo
                </div>
                @if ($canViewModule('Compras'))
                    <a href="{{ route('compras.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('compras.*') ? 'bg-green-600/20 text-white' : '' }}">Compras</a>
                @endif
                @if ($canViewModule('Compras') || $sessionRole === 'PROVEEDOR' || $sessionRole === 'ALMACEN')
                    <a href="{{ route('entregas.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-green-600/20 text-white' : '' }}">
                        @if($sessionRole === 'PROVEEDOR') Mis entregas @elseif($sessionRole === 'ALMACEN') Gestión entregas @else Entregas @endif
                    </a>
                @endif
                @if ($canViewModule('Insumos'))
                    <a href="{{ route('insumos.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('insumos.*') ? 'bg-green-600/20 text-white' : '' }}">Insumos</a>
                @endif
                @if ($sessionRole === 'PROVEEDOR')
                    <a href="{{ route('compras.index') }}#ordenes-compra" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('compras.*') ? 'bg-green-600/20 text-white' : '' }}">
                        Mis órdenes de compra
                    </a>
                @endif
                @if ($sessionRole === 'PROVEEDOR')
                    <a href="{{ route('entregas.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('entregas.*') ? 'bg-green-600/20 text-white' : '' }}">Mis entregas</a>
                @endif

                <div class="col-span-2 mt-4 mb-1 px-1 text-[0.73rem] text-slate-400 uppercase font-bold tracking-widest">
                    Administración
                </div>
                @if ($canViewModule('Proveedores'))
                    <a href="{{ route('proveedores.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('proveedores.*') ? 'bg-green-600/20 text-white' : '' }}">Proveedores</a>
                @endif
                @if ($canViewModule('Crear usuarios y otorgar permisos'))
                    <a href="{{ route('permisos.index') }}" class="block px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-green-600/20 transition-colors {{ request()->routeIs('permisos.*') ? 'bg-green-600/20 text-white' : '' }}">Usuarios y Permisos</a>
                @endif
            </nav>

            <div class="mt-8 p-3 border border-white/20 rounded-xl text-sm text-slate-200">
                <div class="font-bold text-white mb-0.5">{{ session('auth_user_nombre', 'Usuario') }}</div>
                <div class="text-xs text-slate-400 mb-3">Rol: {{ session('auth_user_rol', '-') }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 rounded-lg transition-colors shadow-sm active:scale-95">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="p-6">
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
        </main>
    </div>

</body>
</html>