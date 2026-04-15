@extends('layouts.app')

@section('content')
@php
    $proveedorRolKey = 'PROVEEDOR';
@endphp
<div class="container mx-auto px-4 py-8 max-w-4xl space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1">Administracion</div>
            <h1 class="text-3xl font-extrabold text-slate-900">Editar Usuario</h1>
            <p class="mt-1 text-sm text-slate-500">Actualiza datos, rol, empresa vinculada y permisos del usuario.</p>
        </div>
        <a href="{{ route('permisos.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors">
            Volver a Administracion
        </a>
    </div>

    @include('partials.flash-messages')

    @php
        $permisosJson = json_encode($permisosPredeterminados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    @endphp

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm"
        x-data="permisoEditForm({{ $permisosJson }}, '{{ $rolActual }}')"
        x-init="aplicarPermisosPredeterminados(rolSeleccionado)">

        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">Perfil y Acceso</h2>
            <p class="text-sm text-slate-500 mt-0.5">Deja la contrasena en blanco si no deseas cambiarla.</p>
        </div>

        <form method="POST" action="{{ route('permisos.usuarios.update', $usuario->id) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Datos básicos --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex flex-col gap-1.5 md:col-span-2">
                    <label for="nombre" class="text-sm font-semibold text-slate-700">Nombre Completo</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $usuario->name) }}" required
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    @error('nombre')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-1.5 md:col-span-2">
                    <label for="email" class="text-sm font-semibold text-slate-700">Correo Electronico</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    @error('email')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-semibold text-slate-700">Nueva Contrasena</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500"
                        placeholder="Opcional">
                    @error('password')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-sm font-semibold text-slate-700">Confirmar Contrasena</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500"
                        placeholder="Opcional">
                </div>
            </div>

            {{-- Rol y empresa --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 border-t border-slate-100 pt-5">
                <div class="flex flex-col gap-1.5">
                    <label for="rol" class="text-sm font-semibold text-slate-700">Rol de Sistema</label>
                    <select id="rol" name="rol" required
                        x-model="rolSeleccionado"
                        x-on:change="aplicarPermisosPredeterminados($event.target.value)"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach ($rolesDisponibles as $rol)
                            <option value="{{ $rol }}" {{ $rolActual === $rol ? 'selected' : '' }}>{{ $rol }}</option>
                        @endforeach
                    </select>
                    @error('rol')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>

                {{-- Empresa vinculada: visible solo si el rol es PROVEEDOR --}}
                <div class="flex flex-col gap-1.5" x-show="rolSeleccionado === '{{ $proveedorRolKey }}'" x-cloak>
                    <label for="proveedor_id" class="text-sm font-semibold text-slate-700">Empresa Vinculada <span class="text-red-500">*</span></label>
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2 py-1">
                        Obligatorio: selecciona la empresa que se va a ligar al usuario proveedor.
                    </p>
                    <select id="proveedor_id" name="proveedor_id" :required="rolSeleccionado === '{{ $proveedorRolKey }}'"
                        class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Sin vincular</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}"
                                {{ (string) old('proveedor_id', $usuario->proveedor_id) === (string) $proveedor->id ? 'selected' : '' }}>
                                {{ $proveedor->nombre_comercial ?: $proveedor->razon_social }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id')<p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Estado activo --}}
            <div class="border-t border-slate-100 pt-4">
                <input type="hidden" name="activo" value="0">
                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 cursor-pointer">
                    <input type="checkbox" name="activo" value="1"
                        {{ old('activo', (int) $usuario->activo) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Usuario activo</span>
                </label>
            </div>

            {{-- Permisos por módulo --}}
            <div class="space-y-4 border-t border-slate-100 pt-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">Permisos por Modulo</h3>
                        <p class="text-sm text-slate-500">
                            Cargados segun el rol.
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

                <div id="edit-grid" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($modulos as $modulo)
                        @php
                            $permActual = $permisosActuales->get($modulo);
                            $puedeVer = old('modulos') !== null
                                ? in_array($modulo, (array) old('modulos'))
                                : ($permActual !== null);
                            $puedeEditar = old('puede_editar') !== null
                                ? in_array($modulo, (array) old('puede_editar'))
                                : ($permActual && (bool) $permActual->puede_editar);
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100/70">
                            <div class="mb-3 text-sm font-semibold text-slate-900">{{ $modulo }}</div>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="modulos[]" value="{{ $modulo }}"
                                        x-ref="ver_{{ \Str::slug($modulo, '_') }}"
                                        {{ $puedeVer ? 'checked' : '' }}
                                        class="h-4 w-4 rounded text-emerald-600">
                                    <span>Puede ver</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="puede_editar[]" value="{{ $modulo }}"
                                        x-ref="editar_{{ \Str::slug($modulo, '_') }}"
                                        {{ $puedeEditar ? 'checked' : '' }}
                                        class="h-4 w-4 rounded text-emerald-600">
                                    <span>Puede editar</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                <a href="{{ route('permisos.index') }}"
                    class="inline-flex items-center justify-center px-6 py-2.5 border border-slate-300 text-slate-700 rounded-lg font-semibold text-sm hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-sm shadow-sm transition-colors">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </section>
</div>

<script>
function permisoEditForm(predeterminados, rolInicial) {
    return {
        rolSeleccionado: rolInicial,
        predeterminados: predeterminados,

        aplicarPermisosPredeterminados(rol) {
            this.rolSeleccionado = rol;
        },

        permitirTodo() {
            document.querySelectorAll('#edit-grid input[type=checkbox]').forEach(c => c.checked = true);
        },

        limpiarTodo() {
            document.querySelectorAll('#edit-grid input[type=checkbox]').forEach(c => c.checked = false);
        },
    };
}
</script>
@endsection