@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900">Administracion de Usuarios</h1>
            <p class="mt-1 text-sm text-slate-500">Crea usuarios, asigna roles y gestiona permisos por modulo.</p>
        </div>
    </div>

    @include('partials.flash-messages')

    @php
        $permisosJson = json_encode($permisosPredeterminados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $rolInicial = old('rol', $rolesDisponibles[0] ?? '');
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        x-data="permisoForm({{ $permisosJson }}, '{{ $rolInicial }}')"
        x-init="aplicarPermisosPredeterminados(rolSeleccionado)">

        <div class="mb-6 border-b border-slate-100 pb-4">
            <h2 class="text-lg font-bold text-slate-900">Crear Nuevo Usuario</h2>
            <p class="mt-1 text-sm text-slate-500">Define rol y permisos base desde este modulo.</p>
        </div>

        <form method="POST" action="{{ route('permisos.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex flex-col gap-1.5">
                    <label for="nombre" class="text-sm font-semibold text-slate-700">Nombre Completo</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required placeholder="Ej. Juan Perez"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-semibold text-slate-700">Correo Electronico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="usuario@gmail.com"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="rol" class="text-sm font-semibold text-slate-700">Rol de Sistema</label>
                    <select id="rol" name="rol" required
                        x-model="rolSeleccionado"
                        x-on:change="aplicarPermisosPredeterminados($event.target.value)"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach ($rolesDisponibles as $rol)
                            <option value="{{ $rol }}">{{ $rol }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-semibold text-slate-700">Contrasena</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex flex-col gap-1.5 md:col-span-2">
                    <label for="password_confirmation" class="text-sm font-semibold text-slate-700">Confirmar Contrasena</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                {{-- Empresa vinculada: visible solo si el rol es PROVEEDOR --}}
                <div class="flex flex-col gap-1.5 md:col-span-2" x-show="rolSeleccionado === 'PROVEEDOR'" x-cloak>
                    <label for="proveedor_id" class="text-sm font-semibold text-slate-700">Empresa Vinculada</label>
                    <select id="proveedor_id" name="proveedor_id"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Sin vincular</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}"
                                {{ (string) old('proveedor_id') === (string) $proveedor->id ? 'selected' : '' }}>
                                {{ $proveedor->nombre_comercial ?: $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="space-y-4 border-t border-slate-100 pt-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">Permisos por Modulo</h3>
                        <p class="text-sm text-slate-500">
                            Cargados automaticamente segun el rol.
                            <span class="font-semibold text-emerald-600" x-text="'(' + rolSeleccionado + ')'"></span>
                            Puedes ajustarlos manualmente.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" x-on:click="permitirTodo()"
                            class="rounded-lg border border-emerald-200 bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200">
                            Permitir todo
                        </button>
                        <button type="button" x-on:click="limpiarTodo()"
                            class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200">
                            Limpiar
                        </button>
                    </div>
                </div>

                <div id="create-grid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($modulos as $modulo)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100/70">
                            <div class="mb-3 text-sm font-semibold text-slate-900">{{ $modulo }}</div>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="modulos[]" value="{{ $modulo }}"
                                        x-ref="ver_{{ \Str::slug($modulo, '_') }}"
                                        class="h-4 w-4 rounded text-emerald-600">
                                    <span>Puede ver</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="puede_editar[]" value="{{ $modulo }}"
                                        x-ref="editar_{{ \Str::slug($modulo, '_') }}"
                                        class="h-4 w-4 rounded text-emerald-600">
                                    <span>Puede editar</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-6 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Registrar Usuario
                </button>
            </div>
        </form>
    </section>

    <script>
    function permisoForm(predeterminados, rolInicial) {
        return {
            rolSeleccionado: rolInicial,
            predeterminados: predeterminados,

            aplicarPermisosPredeterminados(rol) {
                this.rolSeleccionado = rol;

                // Normalizar clave del rol igual que PermisoService::normalizeRoleKey
                const clave = this.normalizarRol(rol);
                const permisos = this.predeterminados[clave] || { modulos: [], puede_editar: [] };

                // Desmarcar todos primero
                this.limpiarTodo();

                // Marcar los que correspondan al rol
                permisos.modulos.forEach(modulo => {
                    const ref = this.$refs['ver_' + this.slugify(modulo)];
                    if (ref) ref.checked = true;
                });
                permisos.puede_editar.forEach(modulo => {
                    const ref = this.$refs['editar_' + this.slugify(modulo)];
                    if (ref) ref.checked = true;
                });
            },

            permitirTodo() {
                document.querySelectorAll('#create-grid input[type=checkbox]').forEach(c => c.checked = true);
            },

            limpiarTodo() {
                document.querySelectorAll('#create-grid input[type=checkbox]').forEach(c => c.checked = false);
            },

            normalizarRol(rol) {
                let r = rol.toLowerCase().trim()
                    .replace(/[-\s]+/g, '_')
                    .replace(/[áàä]/g, 'a')
                    .replace(/[éèë]/g, 'e')
                    .replace(/[íìï]/g, 'i')
                    .replace(/[óòö]/g, 'o')
                    .replace(/[úùü]/g, 'u')
                    .replace(/ñ/g, 'n');
                const mapa = {
                    'super_admin': 'SUPER_ADMIN',
                    'super_administrador': 'SUPER_ADMIN',
                    'administrador': 'SUPER_ADMIN',
                    'admin': 'SUPER_ADMIN',
                    'gerente_produccion': 'GERENTE_PRODUCCION',
                    'gerente_de_produccion': 'GERENTE_PRODUCCION',
                    'supervisor_almacen': 'SUPERVISOR_ALMACEN',
                    'supervisor_de_almacen': 'SUPERVISOR_ALMACEN',
                    'almacen': 'SUPERVISOR_ALMACEN',
                    'operador': 'OPERADOR',
                    'proveedor': 'PROVEEDOR',
                };
                return mapa[r] ?? r.toUpperCase();
            },

            slugify(texto) {
                return texto.toLowerCase().trim()
                    .replace(/[áàä]/g, 'a').replace(/[éèë]/g, 'e')
                    .replace(/[íìï]/g, 'i').replace(/[óòö]/g, 'o')
                    .replace(/[úùü]/g, 'u').replace(/ñ/g, 'n')
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            }
        };
    }
    </script>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-6">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Usuarios Registrados</h2>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-600">Filtrar por rol:</span>
                <a href="{{ route('permisos.index') }}" class="rounded-lg px-3 py-1 text-xs font-bold transition-all {{ !$rolFiltro ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Todos
                </a>
                @foreach ($rolesDisponibles as $rol)
                    <a href="{{ route('permisos.index', ['rol' => $rol]) }}" class="rounded-lg px-3 py-1 text-xs font-bold transition-all {{ $rolFiltro === $rol ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $rol }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">ID</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">Nombre</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">Email</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">Rol</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse ($registros as $registro)
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono text-xs text-slate-400">#{{ $registro['id'] }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $registro['nombre'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $registro['email'] }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-md border border-emerald-200 bg-emerald-100 px-2.5 py-1 text-xs font-semibold uppercase text-emerald-700">
                                    {{ $registro['rol'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2.5 py-1 text-xs font-bold uppercase {{ $registro['estado'] === 'Activo' ? 'border border-green-200 bg-green-100 text-green-700' : 'border border-red-200 bg-red-100 text-red-700' }}">
                                    {{ $registro['estado'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-3">
                                    <a href="{{ route('permisos.usuarios.edit', ['id' => $registro['id']]) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                                        Editar
                                    </a>

                                    @if ($registro['rol'] !== 'ADMIN')
                                        <form method="POST" action="{{ route('permisos.usuarios.destroy', ['id' => $registro['id']]) }}" onsubmit="return confirm('¿Eliminar este usuario? No se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700">
                                                Eliminar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('permisos.toggleEstado', ['id' => $registro['id']]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 text-sm font-semibold text-amber-600 hover:text-amber-700">
                                                {{ $registro['estado'] === 'Activo' ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                No hay usuarios registrados aun.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
