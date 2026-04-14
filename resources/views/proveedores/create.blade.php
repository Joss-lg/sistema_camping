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
                    <label for="razon_social" class="lc-label">Razón social</label>
                    <input id="razon_social" name="razon_social" type="text" value="{{ old('razon_social') }}" required class="lc-input" placeholder="Ej: Acampada Libre S.A.">
                    @error('razon_social')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="nombre_comercial" class="lc-label">Nombre comercial</label>
                    <input id="nombre_comercial" name="nombre_comercial" type="text" value="{{ old('nombre_comercial') }}" class="lc-input" placeholder="Ej: Acampada Libre">
                    @error('nombre_comercial')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="rfc" class="lc-label">RFC</label>
                    <input id="rfc" name="rfc" type="text" value="{{ old('rfc') }}" class="lc-input" maxlength="13" placeholder="XAXX010101000">
                    @error('rfc')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="tipo_proveedor" class="lc-label">Tipo de proveedor</label>
                    <input id="tipo_proveedor" name="tipo_proveedor" type="text" value="{{ old('tipo_proveedor', 'General') }}" required class="lc-input" maxlength="50" placeholder="General, Materia Prima, Logística...">
                    @error('tipo_proveedor')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="estatus" class="lc-label">Estado de relación</label>
                    <select id="estatus" name="estatus" required class="lc-select">
                        @foreach ($estatuses as $estatus)
                            <option value="{{ $estatus->nombre }}" {{ old('estatus', $estatuses->first()->nombre ?? 'Activo') === $estatus->nombre ? 'selected' : '' }}>
                                {{ $estatus->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('estatus')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="contacto_principal" class="lc-label">Contacto principal</label>
                    <input id="contacto_principal" name="contacto_principal" type="text" value="{{ old('contacto_principal') }}" class="lc-input" placeholder="Nombre del agente">
                    @error('contacto_principal')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="email_general" class="lc-label">Correo general</label>
                    <input id="email_general" name="email_general" type="email" value="{{ old('email_general') }}" class="lc-input" placeholder="ventas@proveedor.com">
                    @error('email_general')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="telefono_principal" class="lc-label">Teléfono principal</label>
                    <input id="telefono_principal" name="telefono_principal" type="text" value="{{ old('telefono_principal') }}" class="lc-input" placeholder="+52 000 000 0000">
                    @error('telefono_principal')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field md:col-span-2">
                    <label for="direccion" class="lc-label">Dirección física</label>
                    <textarea id="direccion" name="direccion" rows="3" class="lc-textarea resize-none" placeholder="Calle, número, ciudad y CP">{{ old('direccion') }}</textarea>
                    @error('direccion')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="ciudad" class="lc-label">Ciudad</label>
                    <input id="ciudad" name="ciudad" type="text" value="{{ old('ciudad') }}" class="lc-input" maxlength="100">
                    @error('ciudad')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="estado" class="lc-label">Estado</label>
                    <input id="estado" name="estado" type="text" value="{{ old('estado') }}" class="lc-input" maxlength="100">
                    @error('estado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="codigo_postal" class="lc-label">Código postal</label>
                    <input id="codigo_postal" name="codigo_postal" type="text" value="{{ old('codigo_postal') }}" class="lc-input" maxlength="10">
                    @error('codigo_postal')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="pais" class="lc-label">País</label>
                    <input id="pais" name="pais" type="text" value="{{ old('pais', 'México') }}" class="lc-input" maxlength="100">
                    @error('pais')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
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

                <div class="lc-field">
                    <label for="limite_credito" class="lc-label">Límite de crédito</label>
                    <input id="limite_credito" name="limite_credito" type="number" min="0" step="0.0001" value="{{ old('limite_credito', 0) }}" class="lc-input">
                    @error('limite_credito')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label for="descuento_porcentaje" class="lc-label">Descuento (%)</label>
                    <input id="descuento_porcentaje" name="descuento_porcentaje" type="number" min="0" max="100" step="0.01" value="{{ old('descuento_porcentaje', 0) }}" class="lc-input">
                    @error('descuento_porcentaje')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
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

                <div class="lc-field md:col-span-2">
                    <label for="certificaciones" class="lc-label">Certificaciones</label>
                    <textarea id="certificaciones" name="certificaciones" rows="2" class="lc-textarea resize-none" placeholder="ISO, NOM, registros sanitarios, etc.">{{ old('certificaciones') }}</textarea>
                    @error('certificaciones')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field md:col-span-2">
                    <label for="notas" class="lc-label">Notas internas</label>
                    <textarea id="notas" name="notas" rows="3" class="lc-textarea resize-none" placeholder="Observaciones operativas, incidencias o acuerdos.">{{ old('notas') }}</textarea>
                    @error('notas')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
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
