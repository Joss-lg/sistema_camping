@extends('layouts.app')

@section('content')
<div class="lc-page max-w-5xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Nuevo proveedor</h1>
            <p class="lc-subtitle">Registra un proveedor con la información comercial mínima necesaria para compras, abastecimiento y seguimiento.</p>
        </div>
        <a href="{{ route('proveedores.index') }}" class="lc-btn-secondary">Volver al listado</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Información del proveedor</h2>
                <p class="lc-section-subtitle">Captura solo los datos necesarios para que compras y almacén trabajen con contexto suficiente.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('proveedores.store') }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="lc-field md:col-span-2">
                    <label for="nombre" class="lc-label">Nombre de la empresa</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required class="lc-input" placeholder="Ej: Acampada Libre S.A.">
                    @error('nombre')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="contacto" class="lc-label">Persona de contacto</label>
                    <input id="contacto" name="contacto" type="text" value="{{ old('contacto') }}" class="lc-input" placeholder="Nombre del agente">
                    @error('contacto')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="email" class="lc-label">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="lc-input" placeholder="ventas@proveedor.com">
                    @error('email')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="telefono" class="lc-label">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono') }}" class="lc-input" placeholder="+52 000 000 0000">
                    @error('telefono')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="estado_id" class="lc-label">Estado de relación</label>
                    <select id="estado_id" name="estado_id" required class="lc-select">
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" {{ (string) old('estado_id', $estados->first()->id ?? '') === (string) $estado->id ? 'selected' : '' }}>
                                {{ $estado->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field md:col-span-2">
                    <label for="direccion" class="lc-label">Dirección física</label>
                    <textarea id="direccion" name="direccion" rows="3" class="lc-textarea resize-none" placeholder="Calle, número, ciudad y CP">{{ old('direccion') }}</textarea>
                    @error('direccion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="tiempo_entrega_dias" class="lc-label">Tiempo de entrega (días)</label>
                    <input id="tiempo_entrega_dias" name="tiempo_entrega_dias" type="number" min="1" max="120" value="{{ old('tiempo_entrega_dias', 3) }}" class="lc-input">
                    @error('tiempo_entrega_dias')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="dias_credito" class="lc-label">Crédito (días)</label>
                    <input id="dias_credito" name="dias_credito" type="number" min="0" max="365" value="{{ old('dias_credito', 0) }}" class="lc-input">
                    @error('dias_credito')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field md:col-span-2">
                    <label for="condiciones_pago" class="lc-label">Condiciones de pago</label>
                    <input id="condiciones_pago" name="condiciones_pago" type="text" value="{{ old('condiciones_pago') }}" class="lc-input" placeholder="Ej: Transferencia 50/50, neto 30 días">
                    @error('condiciones_pago')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="calificacion" class="lc-label">Calificación de calidad</label>
                    <input id="calificacion" name="calificacion" type="number" min="0" max="5" step="0.1" value="{{ old('calificacion', 0) }}" class="lc-input">
                    @error('calificacion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                <a href="{{ route('proveedores.index') }}" class="lc-btn-secondary">Cancelar</a>
                <button type="submit" class="lc-btn-primary min-w-[190px]" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                    <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar proveedor'"></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
