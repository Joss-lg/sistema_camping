@extends('layouts.app')

@section('content')
<div class="lc-page max-w-6xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Operaciones</div>
            <h1 class="lc-title">Editar orden de produccion</h1>
            <p class="lc-subtitle">Actualiza toda la informacion operativa para mantener la orden consistente.</p>
        </div>
        <a href="{{ route('ordenes-produccion.index') }}" class="lc-btn-secondary">Volver</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Datos de la orden</h2>
                <p class="lc-section-subtitle">Completa los campos de seguimiento, costos y control de calidad.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ordenes-produccion.update', $ordenProduccion) }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="lc-field">
                    <label class="lc-label">Numero de orden</label>
                    <input type="text" name="numero_orden" maxlength="50" value="{{ old('numero_orden', $ordenProduccion->numero_orden) }}" class="lc-input" placeholder="Ej. OP-000123">
                    @error('numero_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Tipo de producto</label>
                    <select name="tipo_producto_id" class="lc-select">
                        <option value="">Seleccione tipo de producto</option>
                        @foreach($tiposProducto as $tipo)
                            <option value="{{ $tipo->id }}" @selected((int) old('tipo_producto_id', $ordenProduccion->tipo_producto_id) === (int) $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_producto_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Responsable</label>
                    <select name="user_id" class="lc-select">
                        <option value="">Sin asignar</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((int) old('user_id', $ordenProduccion->user_id) === (int) $usuario->id)>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de orden</label>
                    <input type="date" name="fecha_orden" value="{{ old('fecha_orden', optional($ordenProduccion->fecha_orden)->format('Y-m-d') ?? now()->toDateString()) }}" class="lc-input">
                    @error('fecha_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha inicio prevista</label>
                    <input type="date" name="fecha_inicio_prevista" value="{{ old('fecha_inicio_prevista', optional($ordenProduccion->fecha_inicio_prevista)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_inicio_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha fin prevista</label>
                    <input type="date" name="fecha_fin_prevista" value="{{ old('fecha_fin_prevista', optional($ordenProduccion->fecha_fin_prevista)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_fin_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha inicio real</label>
                    <input type="date" name="fecha_inicio_real" value="{{ old('fecha_inicio_real', optional($ordenProduccion->fecha_inicio_real)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_inicio_real')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha fin real</label>
                    <input type="date" name="fecha_fin_real" value="{{ old('fecha_fin_real', optional($ordenProduccion->fecha_fin_real)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_fin_real')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Cantidad de produccion</label>
                    <input type="number" step="0.0001" min="0.0001" name="cantidad_produccion" value="{{ old('cantidad_produccion', $ordenProduccion->cantidad_produccion) }}" class="lc-input" placeholder="0.00">
                    @error('cantidad_produccion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Unidad de medida</label>
                    <select name="unidad_medida_id" class="lc-select">
                        <option value="">Seleccione unidad</option>
                        @foreach($unidadesMedida as $unidad)
                            <option value="{{ $unidad->id }}" @selected((int) old('unidad_medida_id', $ordenProduccion->unidad_medida_id) === (int) $unidad->id)>
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
                            <option value="{{ $estado }}" @selected(old('estado', $ordenProduccion->estado) === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Prioridad</label>
                    <select name="prioridad" class="lc-select">
                        <option value="">Seleccione prioridad</option>
                        @foreach(['Baja', 'Media', 'Alta', 'Urgente'] as $prioridad)
                            <option value="{{ $prioridad }}" @selected(old('prioridad', $ordenProduccion->prioridad) === $prioridad)>{{ $prioridad }}</option>
                        @endforeach
                    </select>
                    @error('prioridad')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Costo estimado</label>
                    <input type="number" step="0.01" min="0" name="costo_estimado" value="{{ old('costo_estimado', $ordenProduccion->costo_estimado) }}" class="lc-input" placeholder="0.00">
                    @error('costo_estimado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Costo real</label>
                    <input type="number" step="0.01" min="0" name="costo_real" value="{{ old('costo_real', $ordenProduccion->costo_real) }}" class="lc-input" placeholder="0.00">
                    @error('costo_real')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field lg:col-span-3">
                    <label class="lc-label">Especificaciones especiales</label>
                    <textarea name="especificaciones_especiales" rows="3" class="lc-textarea" placeholder="Tolerancias, materiales o instrucciones especiales">{{ old('especificaciones_especiales', $ordenProduccion->especificaciones_especiales) }}</textarea>
                    @error('especificaciones_especiales')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field lg:col-span-3">
                    <label class="lc-label">Notas</label>
                    <textarea name="notas" rows="3" class="lc-textarea" placeholder="Notas internas de la orden">{{ old('notas', $ordenProduccion->notas) }}</textarea>
                    @error('notas')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 lg:col-span-3">
                    <input type="hidden" name="requiere_calidad" value="0">
                    <input type="checkbox" name="requiere_calidad" value="1" @checked((bool) old('requiere_calidad', $ordenProduccion->requiere_calidad)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Requiere validacion de control de calidad
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <a href="{{ route('ordenes-produccion.index') }}" class="lc-btn-secondary">Cancelar</a>
                <button type="submit" class="lc-btn-primary min-w-[190px]" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                    <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="submitting ? 'Actualizando...' : 'Actualizar orden'"></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
