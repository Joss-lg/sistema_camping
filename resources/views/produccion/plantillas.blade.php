@extends('layouts.app')

@section('content')
<div class="lc-page space-y-6">
    <section class="lc-page-header">
        <div>
            <div class="lc-kicker">Producción / configuración</div>
            <h1 class="lc-title">Plantillas de etapas</h1>
            <p class="lc-subtitle">Define las etapas por producto. Cada nueva orden clonará estas etapas en trazabilidad para mantener historial de ejecución.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('produccion.bom.index') }}" class="lc-btn-secondary">Ir a BOM</a>
            <a href="{{ route('produccion.index') }}" class="lc-btn-secondary">Volver</a>
        </div>
    </section>

    @if (session('ok'))
        <div class="lc-alert lc-alert-success">{{ session('ok') }}</div>
    @endif

    @if ($errors->any())
        <div class="lc-alert lc-alert-danger">
            <div class="font-semibold">No se pudo guardar la etapa.</div>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($canManage)
        <section id="crear-plantilla" class="lc-card p-5 scroll-mt-24">
            <h2 class="text-base font-bold text-slate-800">Nueva etapa de plantilla</h2>
            <form
                method="POST"
                action="{{ route('produccion.plantillas.store') }}"
                x-data="{
                    rows: @js(old('etapas', $prefillEtapas)),
                    showDeleteWarning: false,
                    pendingRemoveIndex: null,
                    pendingRemoveLabel: '',
                    pendingRemoveDescription: '',
                    addRow() {
                        const secuenciaBase = this.rows.length > 0 ? Number(this.rows[this.rows.length - 1].numero_secuencia || this.rows.length) : 0;
                        this.rows.push({
                            id: null,
                            nombre: '',
                            numero_secuencia: secuenciaBase + 1,
                            duracion_estimada_minutos: 30,
                            cantidad_operarios: 1,
                            tipo_etapa: 'Manufactura',
                            descripcion: '',
                            instrucciones_detalladas: '',
                            requiere_validacion: 0,
                            es_etapa_critica: 0,
                        });
                    },
                    removeRow(index) {
                        if (this.rows.length <= 1) {
                            return;
                        }

                        const row = this.rows[index] || {};
                        this.pendingRemoveIndex = index;
                        this.pendingRemoveLabel = (row.nombre || '').trim() || `Etapa ${index + 1}`;
                        this.pendingRemoveDescription = (row.descripcion || '').trim();
                        this.showDeleteWarning = true;
                    },
                    confirmRemove() {
                        if (this.pendingRemoveIndex === null || this.pendingRemoveIndex < 0 || this.pendingRemoveIndex >= this.rows.length) {
                            this.cancelRemove();
                            return;
                        }

                        this.rows.splice(this.pendingRemoveIndex, 1);
                        this.cancelRemove();
                    },
                    cancelRemove() {
                        this.showDeleteWarning = false;
                        this.pendingRemoveIndex = null;
                        this.pendingRemoveLabel = '';
                        this.pendingRemoveDescription = '';
                    },
                    handleEscape(event) {
                        if (event.key === 'Escape' && this.showDeleteWarning) {
                            this.cancelRemove();
                        }
                    },
                    duplicateSequences() {
                        const counts = {};

                        this.rows.forEach((row) => {
                            const seq = Number(row.numero_secuencia || 0);
                            if (seq > 0) {
                                counts[seq] = (counts[seq] || 0) + 1;
                            }
                        });

                        return Object.keys(counts)
                            .filter((key) => counts[key] > 1)
                            .map((key) => Number(key));
                    },
                    isSequenceDuplicated(value) {
                        const seq = Number(value || 0);
                        if (seq <= 0) {
                            return false;
                        }

                        return this.duplicateSequences().includes(seq);
                    }
                }"
                x-on:keydown.window="handleEscape($event)"
                class="mt-4 space-y-4"
            >
                @csrf
                <input type="hidden" name="modo_actualizacion" value="{{ $modoActualizacion ? '1' : '0' }}">

                @if($modoActualizacion)
                    <div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700">
                        Modo actualizacion: se cargaron etapas existentes del producto para editar/agregar en un solo guardado.
                    </div>
                @endif

                <div
                    x-show="duplicateSequences().length > 0"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"
                >
                    Hay secuencias duplicadas en el formulario. Corrígelas antes de guardar.
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="lc-field lg:col-span-2">
                        <label class="lc-label">Producto <span class="text-red-500">*</span></label>
                        <select name="producto_id" required class="lc-select">
                            <option value="">Selecciona</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" @selected((int) old('producto_id', request('editar_producto')) === (int) $producto->id)>
                                    {{ $producto->nombre }} ({{ $producto->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end justify-end">
                        <button type="button" class="lc-btn-secondary w-full md:w-auto" x-on:click="addRow()">+ Agregar etapa</button>
                    </div>
                </div>

                <template x-for="(row, index) in rows" :key="index">
                    <article class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-800" x-text="`Etapa ${index + 1}`"></h3>
                            <button type="button" class="text-xs font-semibold text-rose-600 hover:text-rose-700" x-on:click="removeRow(index)" x-show="rows.length > 1">Quitar</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <input type="hidden" x-model="row.id" :name="`etapas[${index}][id]`">

                            <div class="lc-field">
                                <label class="lc-label">Nombre etapa <span class="text-red-500">*</span></label>
                                <input type="text" maxlength="100" required class="lc-input" placeholder="Ej: Corte de tela" x-model="row.nombre" :name="`etapas[${index}][nombre]`">
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Secuencia <span class="text-red-500">*</span></label>
                                <input type="number" min="1" max="999" required class="lc-input" x-model="row.numero_secuencia" :name="`etapas[${index}][numero_secuencia]`">
                                <p x-show="isSequenceDuplicated(row.numero_secuencia)" class="mt-1 text-xs font-semibold text-rose-600">Secuencia duplicada.</p>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Duración estimada (min) <span class="text-red-500">*</span></label>
                                <input type="number" min="1" max="10080" required class="lc-input" x-model="row.duracion_estimada_minutos" :name="`etapas[${index}][duracion_estimada_minutos]`">
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Operarios</label>
                                <input type="number" min="1" max="100" class="lc-input" x-model="row.cantidad_operarios" :name="`etapas[${index}][cantidad_operarios]`">
                            </div>

                            <div class="lc-field md:col-span-2">
                                <label class="lc-label">Tipo de etapa</label>
                                <input type="text" maxlength="50" class="lc-input" placeholder="Corte, Costura, Ensamble..." x-model="row.tipo_etapa" :name="`etapas[${index}][tipo_etapa]`">
                            </div>

                            <div class="lc-field md:col-span-2 lg:col-span-3">
                                <label class="lc-label">Descripción</label>
                                <textarea rows="2" class="lc-input" placeholder="Detalle de la etapa" x-model="row.descripcion" :name="`etapas[${index}][descripcion]`"></textarea>
                            </div>

                            <div class="lc-field md:col-span-2 lg:col-span-3">
                                <label class="lc-label">Instrucciones detalladas</label>
                                <textarea rows="3" class="lc-input" placeholder="Checklist operativo" x-model="row.instrucciones_detalladas" :name="`etapas[${index}][instrucciones_detalladas]`"></textarea>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" :name="`etapas[${index}][requiere_validacion]`" value="0">
                                <input type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" x-model="row.requiere_validacion" :name="`etapas[${index}][requiere_validacion]`">
                                Requiere validación manual
                            </label>

                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" :name="`etapas[${index}][es_etapa_critica]`" value="0">
                                <input type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600" x-model="row.es_etapa_critica" :name="`etapas[${index}][es_etapa_critica]`">
                                Marcar como etapa crítica
                            </label>
                        </div>
                    </article>
                </template>

                <div>
                    <button type="submit" class="lc-btn-primary" :disabled="duplicateSequences().length > 0" :class="duplicateSequences().length > 0 ? 'opacity-60 cursor-not-allowed' : ''">Guardar etapa en plantilla</button>
                </div>

                <template x-teleport="body">
                    <div
                        x-cloak
                        x-show="showDeleteWarning"
                        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                        x-on:click.self="cancelRemove()"
                        x-transition.opacity
                    >
                        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

                        <div class="relative w-full max-w-lg rounded-2xl border border-rose-200 bg-white p-5 shadow-2xl" x-transition>
                            <h3 class="text-base font-bold text-slate-900">Confirmar eliminación de etapa</h3>
                            <p class="mt-2 text-sm text-slate-600">¿Está seguro de que quiere eliminar esta etapa?</p>

                            <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                <div class="font-semibold" x-text="pendingRemoveLabel"></div>
                                <div class="mt-1 text-xs text-rose-600" x-show="pendingRemoveDescription" x-text="pendingRemoveDescription"></div>
                            </div>

                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" class="lc-btn-secondary" x-on:click="cancelRemove()">No</button>
                                <button type="button" class="lc-btn-primary !bg-rose-600 hover:!bg-rose-700" x-on:click="confirmRemove()">Sí</button>
                            </div>
                        </div>
                    </div>
                </template>
            </form>
        </section>
    @endif

    <section class="lc-card p-5">
        <h2 class="text-base font-bold text-slate-800">Etapas configuradas por producto</h2>
        <p class="mt-1 text-sm text-slate-600">Estas etapas son la fuente para generar trazabilidad al crear nuevas órdenes de producción.</p>

        @if($plantillas->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                No hay plantillas de etapas activas todavía.
            </div>
        @else
            <div class="mt-4 space-y-4">
                @foreach($plantillas as $grupo)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800">
                                {{ $grupo->producto->nombre ?? 'Producto' }}
                                <span class="text-slate-500 font-medium">({{ $grupo->producto->sku ?? 'N/A' }})</span>
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">{{ $grupo->etapas->count() }} etapa(s)</span>
                                @if ($canManage)
                                    <a
                                        href="{{ route('produccion.plantillas.index', ['editar_producto' => $grupo->producto->id]) }}#crear-plantilla"
                                        class="lc-btn-secondary !px-3 !py-1.5 !text-xs"
                                    >
                                        Actualizar
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach($grupo->etapas as $etapa)
                                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 p-2 text-sm">
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-800 px-2 text-xs font-bold text-white">
                                        {{ $etapa->numero_secuencia }}
                                    </span>
                                    <span class="font-semibold text-slate-800">{{ $etapa->nombre }}</span>
                                    <span class="text-xs text-slate-600">{{ $etapa->tipo_etapa }}</span>
                                    <span class="text-xs text-slate-500">{{ $etapa->duracion_estimada_minutos }} min</span>
                                    <span class="text-xs text-slate-500">{{ $etapa->cantidad_operarios }} op.</span>
                                    @if($etapa->requiere_validacion)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Validación</span>
                                    @endif
                                    @if($etapa->es_etapa_critica)
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Crítica</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
