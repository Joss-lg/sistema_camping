@extends('layouts.app')

@section('content')
<div class="lc-page max-w-6xl">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Abastecimiento</div>
            <h1 class="lc-title">Editar orden de compra #{{ $ordenCompra->numero_orden }}</h1>
            <p class="lc-subtitle">Actualiza encabezado comercial y lineas de detalle para evitar huecos de actualizacion.</p>
        </div>
        <a href="{{ route('ordenes-compra.index') }}" class="lc-btn-secondary">Volver</a>
    </section>

    @include('partials.flash-messages')

    <section class="lc-card overflow-hidden">
        <div class="lc-card-header">
            <div>
                <h2 class="lc-section-title">Encabezado de orden</h2>
                <p class="lc-section-subtitle">Mantiene sincronizados proveedor, costos y referencias logisticas.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ordenes-compra.update', $ordenCompra) }}" class="lc-card-body space-y-6" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="lc-field">
                    <label class="lc-label">Numero de orden</label>
                    <input type="text" name="numero_orden" maxlength="50" value="{{ old('numero_orden', $ordenCompra->numero_orden) }}" class="lc-input">
                    @error('numero_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Proveedor</label>
                    <select id="proveedor_id" name="proveedor_id" class="lc-select">
                        <option value="">Seleccione proveedor</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $ordenCompra->proveedor_id) === (int) $proveedor->id)>{{ $proveedor->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de orden</label>
                    <input type="date" name="fecha_orden" value="{{ old('fecha_orden', optional($ordenCompra->fecha_orden)->format('Y-m-d') ?? now()->toDateString()) }}" class="lc-input">
                    @error('fecha_orden')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de entrega prevista</label>
                    <input id="fecha_entrega_prevista" type="date" name="fecha_entrega_prevista" value="{{ old('fecha_entrega_prevista', optional($ordenCompra->fecha_entrega_prevista)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_entrega_prevista')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field xl:col-span-2">
                    <div id="proveedor-auto-info" class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Datos del proveedor seleccionados</p>
                        <p class="mt-1">Contacto: <span id="proveedor-contacto">N/A</span></p>
                        <p>Email: <span id="proveedor-email">N/A</span> · Tel: <span id="proveedor-telefono">N/A</span></p>
                        <p class="mt-1 text-xs text-slate-500">Los campos autocompletados se pueden editar manualmente antes de guardar.</p>
                    </div>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fecha de entrega real</label>
                    <input type="date" name="fecha_entrega_real" value="{{ old('fecha_entrega_real', optional($ordenCompra->fecha_entrega_real)->format('Y-m-d')) }}" class="lc-input">
                    @error('fecha_entrega_real')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Estado</label>
                    <select name="estado" class="lc-select">
                        @foreach(['Pendiente', 'Confirmada', 'Parcial', 'Recibida', 'Cancelada'] as $estado)
                            <option value="{{ $estado }}" @selected(old('estado', $ordenCompra->estado) === $estado)>{{ $estado }}</option>
                        @endforeach
                    </select>
                    @error('estado')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Impuestos (monto fijo)</label>
                    <input type="number" step="0.01" min="0" name="impuestos" value="{{ old('impuestos', $ordenCompra->impuestos) }}" class="lc-input" data-fin="impuestos">
                    <p class="lc-help">Se suma al subtotal final de la orden.</p>
                    @error('impuestos')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Descuentos (monto fijo)</label>
                    <input type="number" step="0.01" min="0" name="descuentos" value="{{ old('descuentos', $ordenCompra->descuentos) }}" class="lc-input" data-fin="descuentos">
                    <p class="lc-help">Se resta al subtotal final de la orden.</p>
                    @error('descuentos')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Costo de flete</label>
                    <input type="number" step="0.01" min="0" name="costo_flete" value="{{ old('costo_flete', $ordenCompra->costo_flete) }}" class="lc-input" data-fin="flete">
                    @error('costo_flete')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Condiciones de pago</label>
                    <input id="condiciones_pago" type="text" name="condiciones_pago" maxlength="100" value="{{ old('condiciones_pago', $ordenCompra->condiciones_pago) }}" class="lc-input">
                    @error('condiciones_pago')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Incoterm</label>
                    <input type="text" name="incoterm" maxlength="20" value="{{ old('incoterm', $ordenCompra->incoterm) }}" class="lc-input" placeholder="Ej: EXW, FOB, CIF">
                    @error('incoterm')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>


                <div class="lc-field">
                    <label class="lc-label">clave de rastreo</label>
                    <input type="text" name="numero_contenedor" maxlength="100" value="{{ old('numero_contenedor', $ordenCompra->numero_contenedor) }}" class="lc-input">
                    @error('numero_contenedor')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field">
                    <label class="lc-label">Numero AWB</label>
                    <input type="text" name="numero_awb" maxlength="100" value="{{ old('numero_awb', $ordenCompra->numero_awb) }}" class="lc-input">
                    @error('numero_awb')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lc-field xl:col-span-2">
                    <label class="lc-label">Notas</label>
                    <textarea name="notas" rows="3" class="lc-textarea">{{ old('notas', $ordenCompra->notas) }}</textarea>
                    @error('notas')<p class="lc-help text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            @php
                $detallesViejos = old('detalles');
                $detallesFormulario = is_array($detallesViejos)
                    ? $detallesViejos
                    : $ordenCompra->detalles->map(function ($detalle) {
                        return [
                            'id' => $detalle->id,
                            'insumo_id' => $detalle->insumo_id,
                            'unidad_medida_id' => $detalle->unidad_medida_id,
                            'cantidad_solicitada' => $detalle->cantidad_solicitada,
                            'precio_unitario' => $detalle->precio_unitario,
                            'descuento_porcentaje' => $detalle->descuento_porcentaje,
                            'fecha_entrega_esperada_linea' => optional($detalle->fecha_entrega_esperada_linea)->format('Y-m-d'),
                            'notas' => $detalle->notas,
                        ];
                    })->toArray();

                if (count($detallesFormulario) === 0) {
                    $detallesFormulario[] = [
                        'id' => null,
                        'insumo_id' => '',
                        'unidad_medida_id' => '',
                        'cantidad_solicitada' => '',
                        'precio_unitario' => '',
                        'descuento_porcentaje' => '',
                        'fecha_entrega_esperada_linea' => '',
                        'notas' => '',
                    ];
                }
            @endphp

            <div class="lc-card-muted p-4">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="lc-section-title">Detalles de insumos</h3>
                        <p class="lc-section-subtitle">Ajusta lineas existentes o agrega nuevas para recalcular montos correctamente.</p>
                    </div>
                    <button type="button" class="lc-btn-secondary" onclick="agregarDetalleCompra();">
                        Agregar insumo
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="lc-table min-w-[1080px]">
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
                        <tbody id="detalles-body">
                            @foreach($detallesFormulario as $index => $detalle)
                                <tr>
                                    <td>
                                        <input type="hidden" name="detalles[{{ $index }}][id]" data-base-name="id" value="{{ $detalle['id'] ?? '' }}">
                                        <select name="detalles[{{ $index }}][insumo_id]" data-base-name="insumo_id" class="lc-select min-w-[180px] py-2" required>
                                            <option value="">Seleccione</option>
                                            @foreach($insumos as $insumo)
                                                <option value="{{ $insumo->id }}" @selected((string) ($detalle['insumo_id'] ?? '') === (string) $insumo->id)>{{ $insumo->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="detalles[{{ $index }}][unidad_medida_id]" data-base-name="unidad_medida_id" class="lc-select min-w-[140px] py-2" required>
                                            <option value="">Seleccione</option>
                                            @foreach($unidades as $unidad)
                                                <option value="{{ $unidad->id }}" @selected((string) ($detalle['unidad_medida_id'] ?? '') === (string) $unidad->id)>{{ $unidad->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" min="0.01" name="detalles[{{ $index }}][cantidad_solicitada]" data-base-name="cantidad_solicitada" value="{{ $detalle['cantidad_solicitada'] ?? '' }}" class="lc-input py-2 min-w-[120px]" required></td>
                                    <td><input type="number" step="0.01" min="0" name="detalles[{{ $index }}][precio_unitario]" data-base-name="precio_unitario" value="{{ $detalle['precio_unitario'] ?? '' }}" class="lc-input py-2 min-w-[120px]" required></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="detalles[{{ $index }}][descuento_porcentaje]" data-base-name="descuento_porcentaje" value="{{ $detalle['descuento_porcentaje'] ?? '' }}" class="lc-input py-2 min-w-[120px]"></td>
                                    <td><input type="date" name="detalles[{{ $index }}][fecha_entrega_esperada_linea]" data-base-name="fecha_entrega_esperada_linea" value="{{ $detalle['fecha_entrega_esperada_linea'] ?? '' }}" class="lc-input py-2 min-w-[150px]"></td>
                                    <td><input type="text" name="detalles[{{ $index }}][notas]" data-base-name="notas" value="{{ $detalle['notas'] ?? '' }}" class="lc-input py-2 min-w-[180px]"></td>
                                    <td class="text-right align-middle">
                                        <button type="button" class="lc-icon-btn lc-icon-btn-danger" aria-label="Quitar detalle" onclick="this.closest('tr').remove(); renumerarDetallesCompra();">X</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
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
                @error('detalles.*.insumo_id')<p class="lc-help mt-3 text-red-600">{{ $message }}</p>@enderror
                @error('detalles.*.unidad_medida_id')<p class="lc-help mt-3 text-red-600">{{ $message }}</p>@enderror
                @error('detalles.*.cantidad_solicitada')<p class="lc-help mt-3 text-red-600">{{ $message }}</p>@enderror
                @error('detalles.*.precio_unitario')<p class="lc-help mt-3 text-red-600">{{ $message }}</p>@enderror

                @php
                    $proveedoresMeta = $proveedores->mapWithKeys(function ($proveedor) {
                        $contactoPrincipal = $proveedor->contactos->firstWhere('es_contacto_principal', true);
                        $contacto = $contactoPrincipal ?: $proveedor->contactos->first();

                        return [
                            (string) $proveedor->id => [
                                'condiciones_pago' => $proveedor->condiciones_pago,
                                'tiempo_entrega_dias' => (int) ($proveedor->tiempo_entrega_dias ?? 0),
                                'email_general' => $proveedor->email_general,
                                'telefono_principal' => $proveedor->telefono_principal,
                                'contacto_nombre' => $contacto?->nombre_completo,
                                'contacto_email' => $contacto?->email,
                                'contacto_telefono' => $contacto?->telefono_movil ?: $contacto?->telefono,
                            ],
                        ];
                    });
                @endphp

                <script>
                    let detalleIndexCompra = {{ count($detallesFormulario) }};
                    const insumosCompra = @json($insumos);
                    const unidadesCompra = @json($unidades);
                    const proveedoresMeta = @json($proveedoresMeta);
                    const camposTocadosProveedor = new Set();

                    function sumarDias(fechaBase, dias) {
                        const fecha = new Date(fechaBase);
                        if (Number.isNaN(fecha.getTime())) {
                            return '';
                        }

                        fecha.setDate(fecha.getDate() + Math.max(0, dias));
                        const y = fecha.getFullYear();
                        const m = String(fecha.getMonth() + 1).padStart(2, '0');
                        const d = String(fecha.getDate()).padStart(2, '0');
                        return `${y}-${m}-${d}`;
                    }

                    function aplicarDatosProveedor() {
                        const proveedorSelect = document.getElementById('proveedor_id');
                        const condicionesInput = document.getElementById('condiciones_pago');
                        const fechaInput = document.getElementById('fecha_entrega_prevista');
                        const info = document.getElementById('proveedor-auto-info');
                        const proveedorId = proveedorSelect?.value ? String(proveedorSelect.value) : '';
                        const meta = proveedorId ? proveedoresMeta[proveedorId] : null;

                        if (!meta) {
                            info?.classList.add('hidden');
                            return;
                        }

                        if (!camposTocadosProveedor.has('condiciones_pago') && condicionesInput && !condicionesInput.value.trim() && meta.condiciones_pago) {
                            condicionesInput.value = meta.condiciones_pago;
                        }

                        if (!camposTocadosProveedor.has('fecha_entrega_prevista') && fechaInput && !fechaInput.value) {
                            fechaInput.value = sumarDias(new Date(), Number(meta.tiempo_entrega_dias || 0));
                        }

                        document.getElementById('proveedor-contacto').textContent = meta.contacto_nombre || 'N/A';
                        document.getElementById('proveedor-email').textContent = meta.contacto_email || meta.email_general || 'N/A';
                        document.getElementById('proveedor-telefono').textContent = meta.contacto_telefono || meta.telefono_principal || 'N/A';
                        info?.classList.remove('hidden');
                    }

                    function renumerarDetallesCompra() {
                        const rows = Array.from(document.querySelectorAll('#detalles-body tr'));
                        rows.forEach((row, rowIndex) => {
                            row.querySelectorAll('select, input').forEach((field) => {
                                const baseName = field.dataset.baseName;
                                if (baseName) {
                                    field.name = `detalles[${rowIndex}][${baseName}]`;
                                }
                            });
                        });

                        detalleIndexCompra = rows.length;
                    }

                    function agregarDetalleCompra() {
                        const tbody = document.getElementById('detalles-body');
                        const row = document.createElement('tr');
                        const rowIndex = detalleIndexCompra;

                        row.innerHTML = `
                            <td>
                                <input type="hidden" name="detalles[${rowIndex}][id]" data-base-name="id" value="">
                                <select name="detalles[${rowIndex}][insumo_id]" data-base-name="insumo_id" class="lc-select min-w-[180px] py-2" required>
                                    <option value="">Seleccione</option>
                                    ${insumosCompra.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('')}
                                </select>
                            </td>
                            <td>
                                <select name="detalles[${rowIndex}][unidad_medida_id]" data-base-name="unidad_medida_id" class="lc-select min-w-[140px] py-2" required>
                                    <option value="">Seleccione</option>
                                    ${unidadesCompra.map(u => `<option value="${u.id}">${u.nombre}</option>`).join('')}
                                </select>
                            </td>
                            <td><input type="number" step="0.01" min="0.01" name="detalles[${rowIndex}][cantidad_solicitada]" data-base-name="cantidad_solicitada" class="lc-input py-2 min-w-[120px]" required></td>
                            <td><input type="number" step="0.01" min="0" name="detalles[${rowIndex}][precio_unitario]" data-base-name="precio_unitario" class="lc-input py-2 min-w-[120px]" required></td>
                            <td><input type="number" step="0.01" min="0" max="100" name="detalles[${rowIndex}][descuento_porcentaje]" data-base-name="descuento_porcentaje" class="lc-input py-2 min-w-[120px]"></td>
                            <td><input type="date" name="detalles[${rowIndex}][fecha_entrega_esperada_linea]" data-base-name="fecha_entrega_esperada_linea" class="lc-input py-2 min-w-[150px]"></td>
                            <td><input type="text" name="detalles[${rowIndex}][notas]" data-base-name="notas" class="lc-input py-2 min-w-[180px]"></td>
                            <td class="text-right align-middle">
                                <button type="button" class="lc-icon-btn lc-icon-btn-danger" aria-label="Quitar detalle" onclick="this.closest('tr').remove(); renumerarDetallesCompra();">X</button>
                            </td>
                        `;

                        tbody.appendChild(row);
                        detalleIndexCompra++;
                        calcularResumenOrdenCompra();
                    }

                    function valorNumeroCompra(value) {
                        const n = parseFloat(value);
                        return Number.isFinite(n) ? n : 0;
                    }

                    function formatoMonedaCompra(valor) {
                        return `$${valor.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }

                    function calcularResumenOrdenCompra() {
                        let subtotal = 0;

                        document.querySelectorAll('#detalles-body tr').forEach((row) => {
                            const cantidad = valorNumeroCompra(row.querySelector('[data-base-name="cantidad_solicitada"]')?.value);
                            const precio = valorNumeroCompra(row.querySelector('[data-base-name="precio_unitario"]')?.value);
                            const descuentoPct = Math.max(0, Math.min(100, valorNumeroCompra(row.querySelector('[data-base-name="descuento_porcentaje"]')?.value)));

                            const base = cantidad * precio;
                            const subtotalLinea = base - (base * (descuentoPct / 100));
                            subtotal += subtotalLinea;
                        });

                        const impuestos = valorNumeroCompra(document.querySelector('[data-fin="impuestos"]')?.value);
                        const descuentos = valorNumeroCompra(document.querySelector('[data-fin="descuentos"]')?.value);
                        const flete = valorNumeroCompra(document.querySelector('[data-fin="flete"]')?.value);
                        const total = subtotal + impuestos - descuentos + flete;

                        document.getElementById('resumen-subtotal').textContent = formatoMonedaCompra(subtotal);
                        document.getElementById('resumen-impuestos').textContent = formatoMonedaCompra(impuestos);
                        document.getElementById('resumen-ajustes').textContent = `-${formatoMonedaCompra(descuentos)} / +${formatoMonedaCompra(flete)}`;
                        document.getElementById('resumen-total').textContent = formatoMonedaCompra(Math.max(0, total));
                    }

                    window.addEventListener('DOMContentLoaded', () => {
                        const proveedorSelect = document.getElementById('proveedor_id');
                        const condicionesInput = document.getElementById('condiciones_pago');
                        const fechaInput = document.getElementById('fecha_entrega_prevista');

                        proveedorSelect?.addEventListener('change', aplicarDatosProveedor);
                        condicionesInput?.addEventListener('input', () => camposTocadosProveedor.add('condiciones_pago'));
                        fechaInput?.addEventListener('input', () => camposTocadosProveedor.add('fecha_entrega_prevista'));

                        document.addEventListener('input', (event) => {
                            if (event.target.closest('#detalles-body') || event.target.matches('[data-fin]')) {
                                calcularResumenOrdenCompra();
                            }
                        });

                        calcularResumenOrdenCompra();
                        aplicarDatosProveedor();
                    });
                </script>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('ordenes-compra.index') }}" class="lc-btn-secondary">Cancelar</a>
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
