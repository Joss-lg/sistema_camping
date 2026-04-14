@extends('layouts.app')

@section('content')
<div class="lc-page max-w-5xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Nueva ubicacion de almacen</h1>
            <p class="lc-subtitle">Define la ubicacion fisica para asignacion de insumos, lotes y producto terminado.</p>
        </div>
        <a href="{{ route('almacenes.index') }}" class="lc-btn-secondary">Volver al listado</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Datos de ubicacion</h2>
                <p class="lc-section-subtitle">Captura identificacion, ubicacion fisica y capacidad disponible.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('almacenes.store') }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="lc-field">
                    <label for="codigo_ubicacion" class="lc-label">Codigo de ubicacion</label>
                    <input id="codigo_ubicacion" name="codigo_ubicacion" type="text" value="{{ old('codigo_ubicacion') }}" required class="lc-input" maxlength="20" placeholder="ALM-A1-R1-N1">
                    @error('codigo_ubicacion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="nombre" class="lc-label">Nombre</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required class="lc-input" maxlength="100" placeholder="Almacen principal - Rack A1">
                    @error('nombre')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="tipo" class="lc-label">Tipo</label>
                    <select id="tipo" name="tipo" required class="lc-select">
                        @foreach ($tiposCatalogo as $itemTipo)
                            <option value="{{ $itemTipo }}" @selected(old('tipo', 'Materia Prima') === $itemTipo)>{{ $itemTipo }}</option>
                        @endforeach
                    </select>
                    @error('tipo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="activo" class="lc-label">Estado</label>
                    <select id="activo" name="activo" required class="lc-select">
                        <option value="1" @selected((string) old('activo', '1') === '1')>Activo</option>
                        <option value="0" @selected((string) old('activo') === '0')>Inactivo</option>
                    </select>
                    @error('activo')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="seccion" class="lc-label">Seccion</label>
                    <input id="seccion" name="seccion" type="text" value="{{ old('seccion') }}" class="lc-input" maxlength="50">
                    @error('seccion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="estante" class="lc-label">Estante</label>
                    <input id="estante" name="estante" type="text" value="{{ old('estante') }}" class="lc-input" maxlength="20">
                    @error('estante')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="nivel" class="lc-label">Nivel</label>
                    <input id="nivel" name="nivel" type="text" value="{{ old('nivel') }}" class="lc-input" maxlength="20">
                    @error('nivel')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="capacidad_maxima" class="lc-label">Capacidad maxima</label>
                    <input id="capacidad_maxima" name="capacidad_maxima" type="number" min="0" step="0.0001" value="{{ old('capacidad_maxima') }}" class="lc-input" placeholder="Opcional">
                    @error('capacidad_maxima')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field md:col-span-2">
                    <label for="capacidad_actual" class="lc-label">Capacidad actual</label>
                    <input id="capacidad_actual" name="capacidad_actual" type="number" min="0" step="0.0001" value="{{ old('capacidad_actual', 0) }}" class="lc-input">
                    @error('capacidad_actual')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                <a href="{{ route('almacenes.index') }}" class="lc-btn-secondary">Cancelar</a>
                <button type="submit" class="lc-btn-primary min-w-[190px]" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                    <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar ubicacion'"></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
