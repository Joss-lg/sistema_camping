@extends('layouts.app')

@section('content')
<div class="lc-page max-w-6xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Crear orden de compra</h1>
            <p class="lc-subtitle">Registra la orden, condiciones comerciales y detalles de insumo desde una sola pantalla de captura.</p>
        </div>
        <a href="{{ route('ordenes-compra.index') }}" class="lc-btn-secondary">Volver</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Encabezado de orden</h2>
                <p class="lc-section-subtitle">Define proveedor, entrega esperada y condiciones antes de agregar las líneas de compra.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('ordenes-compra.store') }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="lc-field">
                    <label class="lc-label">Proveedor</label>
                    <select name="proveedor_id" class="lc-select">
                        <option value="">Seleccione proveedor</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected((string) old('proveedor_id', $prefillProveedorId ?? '') === (string) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de entrega prevista</label>
                    <input type="date" name="fecha_entrega_prevista" value="{{ old('fecha_entrega_prevista') }}" class="lc-input">
                    @error('fecha_entrega_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field xl:col-span-2">
                    <label class="lc-label">Notas</label>
                    <textarea name="notas" rows="3" class="lc-textarea">{{ old('notas') }}</textarea>
                    @error('notas')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Impuestos (monto fijo)</label>
                    <input type="number" step="0.01" min="0" name="impuestos" value="{{ old('impuestos') }}" class="lc-input" data-fin="impuestos">
                    <p class="lc-help">Se suma al subtotal final de la orden.</p>
                    @error('impuestos')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Descuentos (monto fijo)</label>
                    <input type="number" step="0.01" min="0" name="descuentos" value="{{ old('descuentos') }}" class="lc-input" data-fin="descuentos">
                    <p class="lc-help">Se resta al subtotal final de la orden.</p>
                    @error('descuentos')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Costo de flete</label>
                    <input type="number" step="0.01" min="0" name="costo_flete" value="{{ old('costo_flete') }}" class="lc-input" data-fin="flete">
                    @error('costo_flete')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Condiciones de pago</label>
                    <input type="text" name="condiciones_pago" value="{{ old('condiciones_pago') }}" class="lc-input">
                    @error('condiciones_pago')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Incoterm</label>
                    <input type="text" name="incoterm" value="{{ old('incoterm') }}" maxlength="20" class="lc-input" placeholder="Ej: EXW, FOB, CIF">
                    @error('incoterm')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Folio proveedor</label>
                    <input type="text" name="numero_folio_proveedor" value="{{ old('numero_folio_proveedor') }}" maxlength="100" class="lc-input">
                    @error('numero_folio_proveedor')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Numero de contenedor</label>
                    <input type="text" name="numero_contenedor" value="{{ old('numero_contenedor') }}" maxlength="100" class="lc-input">
                    @error('numero_contenedor')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Numero AWB</label>
                    <input type="text" name="numero_awb" value="{{ old('numero_awb') }}" maxlength="100" class="lc-input">
                    @error('numero_awb')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="lc-card-muted p-4">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="lc-section-title">Detalles de insumos</h3>
                        <p class="lc-section-subtitle">Agrega una o varias líneas de compra sin salir del formulario.</p>
                    </div>
                    <button type="button" onclick="agregarDetalle()" class="lc-btn-secondary">Agregar insumo</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="lc-table min-w-[980px]">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Unidad</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Descuento %</th>
                                <th>Fecha entrega</th>
                                <th>Notas</th>
                                <th class="text-right">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="detalles-body"></tbody>
                    </table>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="lc-card-muted p-3">
                        <div class="text-xs text-slate-500">Subtotal líneas</div>
                        <div id="resumen-subtotal" class="text-lg font-semibold text-slate-900">$0.00</div>
                    </div>
                    <div class="lc-card-muted p-3">
                        <div class="text-xs text-slate-500">Impuestos</div>
                        <div id="resumen-impuestos" class="text-lg font-semibold text-slate-900">$0.00</div>
                    </div>
                    <div class="lc-card-muted p-3">
                        <div class="text-xs text-slate-500">Descuentos y flete</div>
                        <div id="resumen-ajustes" class="text-lg font-semibold text-slate-900">-$0.00 / +$0.00</div>
                    </div>
                    <div class="lc-card-muted p-3 border border-sky-200 bg-sky-50/60">
                        <div class="text-xs text-slate-500">Total estimado</div>
                        <div id="resumen-total" class="text-xl font-bold text-sky-800">$0.00</div>
                    </div>
                </div>
                <p class="lc-help mt-3">Formula: subtotal lineas + impuestos - descuentos + flete. El descuento por linea (%) se aplica antes del subtotal general.</p>
                @error('detalles')<p class="lc-help mt-3 text-red-600">{{ $message }}</p>@enderror
            </div>

            <script>
                let insumos = @json($insumos);
                let unidades = @json($unidades);
                let prefillDetalle = @json($prefillDetalle ?? null);
                let detalleIndex = 0;

                function renumerarFilasDetalles() {
                    const rows = Array.from(document.querySelectorAll('#detalles-body tr'));
                    rows.forEach((row, rowIndex) => {
                        row.querySelectorAll('select, input').forEach((field) => {
                            const baseName = field.dataset.baseName;
                            if (baseName) {
                                field.name = `detalles[${rowIndex}][${baseName}]`;
                            }
                        });
                    });

                    detalleIndex = rows.length;
                }

                function agregarDetalle(defaults = null) {
                    const tbody = document.getElementById('detalles-body');
                    const row = document.createElement('tr');
                    const rowIndex = detalleIndex;
                    const initial = defaults || {};

                    const renderOptions = (options, selectedValue) => {
                        const selectedNormalized = selectedValue === undefined || selectedValue === null || selectedValue === ''
                            ? ''
                            : String(selectedValue);

                        return options.map((item) => {
                            const itemValue = String(item.id);
                            const selected = itemValue === selectedNormalized ? 'selected' : '';
                            const label = item.nombre ?? item.razon_social ?? `#${itemValue}`;
                            return `<option value="${itemValue}" ${selected}>${label}</option>`;
                        }).join('');
                    };

                    row.innerHTML = `
                        <td>
                            <select name="detalles[${rowIndex}][insumo_id]" data-base-name="insumo_id" class="lc-select min-w-[180px] py-2" required>
                                <option value="">Seleccione</option>
                                ${renderOptions(insumos, initial.insumo_id)}
                            </select>
                        </td>
                        <td>
                            <select name="detalles[${rowIndex}][unidad_medida_id]" data-base-name="unidad_medida_id" class="lc-select min-w-[140px] py-2" required>
                                <option value="">Seleccione</option>
                                ${renderOptions(unidades, initial.unidad_medida_id)}
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="detalles[${rowIndex}][cantidad_solicitada]" data-base-name="cantidad_solicitada" class="lc-input py-2 min-w-[120px]" value="${initial.cantidad_solicitada ?? ''}" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="detalles[${rowIndex}][precio_unitario]" data-base-name="precio_unitario" class="lc-input py-2 min-w-[120px]" value="${initial.precio_unitario ?? ''}" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="detalles[${rowIndex}][descuento_porcentaje]" data-base-name="descuento_porcentaje" class="lc-input py-2 min-w-[120px]" value="${initial.descuento_porcentaje ?? ''}">
                        </td>
                        <td>
                            <input type="date" name="detalles[${rowIndex}][fecha_entrega_esperada_linea]" data-base-name="fecha_entrega_esperada_linea" class="lc-input py-2 min-w-[150px]" value="${initial.fecha_entrega_esperada_linea ?? ''}">
                        </td>
                        <td>
                            <input type="text" name="detalles[${rowIndex}][notas]" data-base-name="notas" class="lc-input py-2 min-w-[180px]" value="${initial.notas ?? ''}">
                        </td>
                        <td class="text-right align-middle">
                            <button type="button" onclick="this.closest('tr').remove(); renumerarFilasDetalles();" class="lc-icon-btn lc-icon-btn-danger" aria-label="Quitar detalle">X</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                    detalleIndex++;
                    calcularResumenOrden();
                }

                function valorNumero(value) {
                    const n = parseFloat(value);
                    return Number.isFinite(n) ? n : 0;
                }

                function formatoMoneda(valor) {
                    return `$${valor.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }

                function calcularResumenOrden() {
                    let subtotal = 0;

                    document.querySelectorAll('#detalles-body tr').forEach((row) => {
                        const cantidad = valorNumero(row.querySelector('[data-base-name="cantidad_solicitada"]')?.value);
                        const precio = valorNumero(row.querySelector('[data-base-name="precio_unitario"]')?.value);
                        const descuentoPct = Math.max(0, Math.min(100, valorNumero(row.querySelector('[data-base-name="descuento_porcentaje"]')?.value)));

                        const base = cantidad * precio;
                        const subtotalLinea = base - (base * (descuentoPct / 100));
                        subtotal += subtotalLinea;
                    });

                    const impuestos = valorNumero(document.querySelector('[data-fin="impuestos"]')?.value);
                    const descuentos = valorNumero(document.querySelector('[data-fin="descuentos"]')?.value);
                    const flete = valorNumero(document.querySelector('[data-fin="flete"]')?.value);
                    const total = subtotal + impuestos - descuentos + flete;

                    document.getElementById('resumen-subtotal').textContent = formatoMoneda(subtotal);
                    document.getElementById('resumen-impuestos').textContent = formatoMoneda(impuestos);
                    document.getElementById('resumen-ajustes').textContent = `-${formatoMoneda(descuentos)} / +${formatoMoneda(flete)}`;
                    document.getElementById('resumen-total').textContent = formatoMoneda(Math.max(0, total));
                }

                window.addEventListener('DOMContentLoaded', () => {
                    if (document.getElementById('detalles-body').children.length === 0) {
                        agregarDetalle(prefillDetalle);
                    }

                    document.addEventListener('input', (event) => {
                        if (event.target.closest('#detalles-body') || event.target.matches('[data-fin]')) {
                            calcularResumenOrden();
                        }
                    });

                    calcularResumenOrden();
                });
            </script>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('ordenes-compra.index') }}" class="lc-btn-secondary">Cancelar</a>
                <button type="submit" class="lc-btn-primary min-w-[170px]" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                    <svg x-cloak x-show="submitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="mr-2 h-4 w-4 animate-spin" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 12a7.25 7.25 0 0 1 14.5 0" />
                    </svg>
                    <span x-text="submitting ? 'Guardando...' : 'Guardar orden'"></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
