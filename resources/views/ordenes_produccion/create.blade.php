@extends('layouts.app')

@section('content')
<div class="lc-page max-w-6xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Operaciones</div>
            <h1 class="lc-title">Crear orden de produccion</h1>
            <p class="lc-subtitle">Captura todos los datos operativos para evitar huecos en la insercion de nuevas ordenes.</p>
        </div>
        <a href="{{ route('ordenes-produccion.index') }}" class="lc-btn-secondary">Volver</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Datos de la orden</h2>
                <p class="lc-section-subtitle">Los campos marcados con * son obligatorios para almacenar correctamente la orden.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ordenes-produccion.store') }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lc-field">
                    <label class="lc-label">Numero de orden</label>
                    <input type="text" name="numero_orden" value="{{ old('numero_orden') }}" maxlength="50" placeholder="Ej. OP-000123" class="lc-input">
                    @error('numero_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Tipo de producto <span class="text-red-500">*</span></label>
                    <select name="tipo_producto_id" required class="lc-select">
                        <option value="">Seleccione tipo de producto</option>
                        @foreach($tiposProducto as $tipo)
                            <option value="{{ $tipo->id }}" @selected((string) old('tipo_producto_id') === (string) $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_producto_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Responsable</label>
                    <select name="user_id" class="lc-select">
                        <option value="">Asignar automaticamente (sesion actual)</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) old('user_id') === (string) $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de orden</label>
                    <input type="date" name="fecha_orden" value="{{ old('fecha_orden', now()->toDateString()) }}" class="lc-input">
                    @error('fecha_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha inicio prevista <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_inicio_prevista" value="{{ old('fecha_inicio_prevista') }}" required class="lc-input">
                    @error('fecha_inicio_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha fin prevista <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_fin_prevista" value="{{ old('fecha_fin_prevista') }}" required class="lc-input">
                    @error('fecha_fin_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Cantidad de produccion <span class="text-red-500">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" name="cantidad_produccion" value="{{ old('cantidad_produccion') }}" required class="lc-input" placeholder="0.00">
                    @error('cantidad_produccion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Unidad de medida <span class="text-red-500">*</span></label>
                    <select name="unidad_medida_id" required class="lc-select">
                        <option value="">Seleccione unidad</option>
                        @foreach($unidadesMedida as $unidad)
                            <option value="{{ $unidad->id }}" @selected((string) old('unidad_medida_id') === (string) $unidad->id)>
                                {{ $unidad->nombre }} ({{ $unidad->abreviatura }})
                            </option>
                        @endforeach
                    </select>
                    @error('unidad_medida_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Estado</label>
                    <select name="estado" class="lc-select">
                        @foreach(['Pendiente', 'En Proceso', 'En Pausa', 'Completada', 'Cancelada'] as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', 'Pendiente') === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Prioridad</label>
                    <select name="prioridad" class="lc-select">
                        @foreach(['Baja', 'Media', 'Alta', 'Urgente'] as $prioridad)
                            <option value="{{ $prioridad }}" @selected(old('prioridad', 'Media') === $prioridad)>{{ $prioridad }}</option>
                        @endforeach
                    </select>
                    @error('prioridad')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Costo estimado</label>
                    <input type="number" step="0.01" min="0" name="costo_estimado" value="{{ old('costo_estimado') }}" class="lc-input" placeholder="0.00">
                    @error('costo_estimado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field lg:col-span-3">
                    <label class="lc-label">Especificaciones especiales</label>
                    <textarea name="especificaciones_especiales" rows="3" class="lc-textarea" placeholder="Tolerancias, materiales o instrucciones especiales de fabricacion">{{ old('especificaciones_especiales') }}</textarea>
                    @error('especificaciones_especiales')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field lg:col-span-3">
                    <label class="lc-label">Notas</label>
                    <textarea name="notas" rows="3" class="lc-textarea" placeholder="Notas internas de la orden">{{ old('notas') }}</textarea>
                    @error('notas')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 lg:col-span-3">
                    <input type="hidden" name="requiere_calidad" value="0">
                    <input type="checkbox" name="requiere_calidad" value="1" @checked(old('requiere_calidad', true)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Requiere validacion de control de calidad
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('ordenes-produccion.index') }}" class="lc-btn-secondary">Cancelar</a>
                <button type="submit" class="lc-btn-primary min-w-[180px]" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                    <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar orden'" ></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
