@extends('layouts.app')

@section('content')
<div class="lc-page space-y-6">
    <div class="lc-page-header">
        <div>
            <div class="lc-kicker">Produccion / seguimiento</div>
            <h1 class="lc-title mt-2">Orden #{{ $ordenView->id }} · {{ $ordenView->producto->nombre ?? 'Producto' }}</h1>
            <p class="lc-subtitle mt-2 max-w-3xl">
                Vista paso a paso para actualizar la orden sin saturar el tablón principal. La secuencia se adapta a las etapas de trazabilidad definidas para este artículo.
            </p>
        </div>
        <a href="{{ route('produccion.index') }}" class="lc-btn-secondary">Volver a producción</a>
    </div>

    @if (session('ok'))
        <div class="lc-alert lc-alert-success">
            {{ session('ok') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="lc-alert lc-alert-danger">
            <div class="font-semibold">No se pudo registrar el consumo.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article class="lc-stat-card">
            <div class="lc-stat-label">Estado actual</div>
            <div class="mt-2 text-xl font-black text-slate-900">{{ str_replace('_', ' ', $ordenView->estado->nombre ?? 'PENDIENTE') }}</div>
        </article>
        <article class="lc-stat-card bg-sky-50/70">
            <div class="lc-stat-label text-sky-600">Consumido total</div>
            <div class="mt-2 text-xl font-black text-sky-700">{{ number_format($resumenConsumos->cantidad_total, 2) }}</div>
        </article>
        <article class="lc-stat-card bg-amber-50/70">
            <div class="lc-stat-label text-amber-700">Merma total</div>
            <div class="mt-2 text-xl font-black text-amber-700">{{ number_format($resumenConsumos->merma_total, 2) }}</div>
        </article>
        <article class="lc-stat-card bg-slate-50/80">
            <div class="lc-stat-label">Eventos de consumo</div>
            <div class="mt-2 text-xl font-black text-slate-800">{{ $resumenConsumos->eventos }}</div>
            <div class="text-[11px] text-slate-500">Merma: {{ number_format($resumenConsumos->merma_porcentaje, 2) }}%</div>
        </article>
    </section>

    <section class="lc-card p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-bold text-slate-800">Línea de tiempo por producto</h2>
            <span class="text-xs text-slate-500">Estado actual: {{ $ordenView->estado->nombre ?? 'PENDIENTE' }}</span>
        </div>
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
            Coincidencia activa: paso {{ $lineaTiempo->paso_actual }} de {{ $lineaTiempo->total_pasos }}.
            Fuente:
            @if($lineaTiempo->fuente === 'trazabilidad')
                etapas de trazabilidad configuradas para este producto.
            @else
                secuencia base de fabricación (fallback).
            @endif
        </div>

        <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
            @foreach ($stepperEtapas as $etapa)
                @php
                    $estilo = match($etapa->estado_ui) {
                        'finalizada' => 'border-emerald-200 bg-emerald-50/80 text-emerald-800 shadow-sm shadow-emerald-100',
                        'actual' => 'border-sky-300 bg-sky-50 text-sky-800 shadow-sm shadow-sky-100 ring-2 ring-sky-200/60',
                        'bloqueada' => 'border-amber-200 bg-amber-50 text-amber-800 shadow-sm shadow-amber-100',
                        default => 'border-slate-200 bg-slate-50/90 text-slate-700',
                    };
                @endphp
                <article class="rounded-2xl border p-4 {{ $estilo }}">
                    <div class="mb-2 inline-flex h-7 min-w-7 items-center justify-center rounded-full border border-current/35 px-2 text-[11px] font-bold">
                        {{ $etapa->numero }}
                    </div>
                    <h3 class="text-sm font-bold">{{ $etapa->nombre }}</h3>
                    <p class="mt-1 text-xs opacity-85">{{ $etapa->estado }}</p>
                </article>
            @endforeach
        </div>
    </section>

    @if ($canManage)
    <section>
        <form method="POST" action="{{ url('produccion/' . $ordenView->id . '/seguimiento') }}" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @csrf
            @method('PATCH')

            <article class="lc-card p-5 space-y-4">
                <h2 class="text-base font-bold text-slate-800">Paso 1 · Estado y etapa</h2>

                <div class="lc-field">
                    <label class="lc-label">Etapa de fabricación</label>
                    <select name="etapa_fabricacion_actual" class="lc-select" @disabled($ordenView->bloqueada_aprobacion)>
                        @foreach (['Corte', 'Costura', 'Ensamblado', 'Acabado'] as $etapaFabricacion)
                            <option value="{{ $etapaFabricacion }}" @selected(($ordenView->etapa_fabricacion_actual ?? 'Corte') === $etapaFabricacion)>{{ strtoupper($etapaFabricacion) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Estado</label>
                    <select name="estado" class="lc-select" @disabled($ordenView->bloqueada_aprobacion)>
                        <option value="PENDIENTE" @selected(($ordenView->estado->nombre ?? '') === 'PENDIENTE')>PENDIENTE</option>
                        <option value="EN_PROCESO" @selected(($ordenView->estado->nombre ?? '') === 'EN_PROCESO')>EN PROCESO</option>
                        <option value="FINALIZADA" @selected(($ordenView->estado->nombre ?? '') === 'FINALIZADA')>FINALIZADA</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Cantidad completada</label>
                    <input
                        type="number"
                        name="cantidad_completada"
                        min="0"
                        step="0.01"
                        value="{{ number_format($ordenView->cantidad_completada, 2, '.', '') }}"
                        class="lc-input"
                        @disabled($ordenView->bloqueada_aprobacion)
                    >
                </div>

                @if ($ordenView->bloqueada_aprobacion)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                        Esta orden está bloqueada por aprobación pendiente
                        @if($ordenView->etapa_pendiente_aprobacion)
                            : {{ $ordenView->etapa_pendiente_aprobacion }}
                        @endif
                    </div>
                @endif
            </article>

            <article class="lc-card p-5 space-y-4">
                <h2 class="text-base font-bold text-slate-800">Paso 2 · Asignación operativa</h2>

                <div class="lc-field">
                    <label class="lc-label">Responsable</label>
                    <select name="responsable_id" class="lc-select" required>
                        <option value="">Selecciona responsable</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) ($ordenView->responsable->id ?? '') === (string) $usuario->id)>
                                {{ $usuario->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Máquina</label>
                    <input type="text" name="maquina_asignada" value="{{ $ordenView->maquina_asignada }}" class="lc-input" placeholder="Ej: Máquina costura Juki #2">
                </div>

                <div class="lc-field">
                    <label class="lc-label">Turno</label>
                    <select name="turno_asignado" class="lc-select">
                        <option value="">Sin turno</option>
                        <option value="Manana" @selected(($ordenView->turno_asignado ?? '') === 'Manana')>Mañana</option>
                        <option value="Tarde" @selected(($ordenView->turno_asignado ?? '') === 'Tarde')>Tarde</option>
                        <option value="Noche" @selected(($ordenView->turno_asignado ?? '') === 'Noche')>Noche</option>
                    </select>
                </div>

                <button type="submit" class="lc-btn-primary w-full">Guardar seguimiento (pasos 1 y 2)</button>
                @if ($ordenView->bloqueada_aprobacion)
                    <p class="text-[11px] text-amber-700">Nota: se guardará la asignación, pero el estado no cambiará hasta resolver la aprobación pendiente.</p>
                @endif
            </article>

        </form>

            @if (($ordenView->estado->nombre ?? '') !== 'FINALIZADA' && ($ordenView->estado->nombre ?? '') !== 'CANCELADA')
                <form method="POST" action="{{ route('produccion.cancelar', $ordenView->id) }}" onsubmit="return confirm('¿Deseas cancelar esta orden? Se liberarán los materiales reservados.')">
                    @csrf
                    <button type="submit" class="lc-btn-danger w-full">Cancelar orden</button>
                </form>
            @endif
    </section>
    @else
    <section class="lc-alert lc-alert-warning">
        Tu rol tiene acceso de solo lectura. Puedes consultar el stepper y materiales, pero no actualizar estado ni asignaciones.
    </section>
    @endif

    <section class="lc-card p-5">
        <h2 class="text-base font-bold text-slate-800 mb-4">Paso 3 · Materiales de la orden</h2>
        @if (($ordenView->materialesPlanificados ?? collect())->isEmpty())
            <p class="text-sm text-slate-500">Esta orden no tiene materiales planificados visibles.</p>
        @else
            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($ordenView->materialesPlanificados as $material)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                        <div class="font-semibold text-slate-800">{{ $material->nombre }}</div>
                        <div class="mt-1 text-xs text-slate-600">Plan: {{ number_format($material->cantidad_planificada, 2) }}</div>
                        <div class="text-xs text-slate-600">Uso: {{ number_format($material->cantidad_consumida, 2) }}</div>
                        <div class="text-xs {{ $material->cantidad_merma > 0 ? 'text-amber-700' : 'text-slate-600' }}">
                            Merma: {{ number_format($material->cantidad_merma, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($canManage)
    <section class="lc-card p-5 text-sm space-y-4">
        <h2 class="text-base font-bold text-slate-800">Paso 4 · Registrar consumo de material</h2>

        @if (($ordenView->materialesPlanificados ?? collect())->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                Esta orden no tiene receta/materiales asignados. Define primero su BOM para habilitar consumo controlado.
            </div>
        @endif

        @if (!($ordenView->materialesPlanificados ?? collect())->isEmpty() && !$puedeRegistrarConsumo)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                Los materiales de esta orden no tienen lotes disponibles con stock. Registra entrada de lotes para habilitar el consumo.
            </div>
        @endif

        @if (($materialesBloqueados ?? collect())->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                <div class="font-semibold text-slate-800">Materiales bloqueados por falta de lotes</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($materialesBloqueados as $materialBloqueado)
                        <li>
                            {{ $materialBloqueado->nombre }}
                            (Lotes: {{ number_format($materialBloqueado->stock_lotes, 2) }} | Global: {{ number_format($materialBloqueado->stock, 2) }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('produccion.registrar-consumo') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="orden_produccion_id" value="{{ $ordenView->id }}">
            <input type="hidden" name="redirect_seguimiento" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div class="lc-field">
                    <label class="lc-label">Orden</label>
                    <input type="text" value="#{{ $ordenView->id }} - {{ $ordenView->producto->nombre }}" class="lc-input lc-input-soft" disabled>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Material</label>
                    <select name="material_id" required class="lc-select lc-input-soft" @disabled(!$puedeRegistrarConsumo)>
                        <option value="">Selecciona</option>
                        @foreach ($materialesConsumo as $material)
                            <option value="{{ $material->id }}" @selected((int) old('material_id') === (int) $material->id)>
                                {{ $material->nombre }} (Lotes: {{ number_format($material->stock_lotes, 2) }} | Global: {{ number_format($material->stock, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-500">Solo se muestran materiales con stock disponible en lotes.</p>
                </div>

                <div class="lc-field">
                    <label class="lc-label text-sky-600">Cant. usada</label>
                    <input name="cantidad_usada" type="number" step="0.01" required value="{{ old('cantidad_usada') }}" class="lc-input lc-input-highlight" @disabled(!$puedeRegistrarConsumo)>
                </div>

                <div class="lc-field">
                    <label class="lc-label text-amber-600">Merma (opt)</label>
                    <input name="cantidad_merma" type="number" step="0.01" value="{{ old('cantidad_merma', 0) }}" class="lc-input lc-input-warning" @disabled(!$puedeRegistrarConsumo)>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Motivo merma</label>
                    <input name="motivo_merma" type="text" value="{{ old('motivo_merma') }}" placeholder="..." class="lc-input lc-input-soft text-xs" @disabled(!$puedeRegistrarConsumo)>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Tipo merma</label>
                    <select name="tipo_merma" class="lc-select lc-input-soft text-xs" @disabled(!$puedeRegistrarConsumo)>
                        <option value="">Selecciona</option>
                        @foreach($tiposMerma as $tipoMerma)
                            <option value="{{ $tipoMerma }}" @selected(old('tipo_merma') === $tipoMerma)>{{ $tipoMerma }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="lc-btn-secondary w-full border-sky-600 bg-sky-600 text-white hover:bg-sky-700 hover:text-white" @disabled(!$puedeRegistrarConsumo)>
                Registrar consumo
            </button>
        </form>
    </section>
    @endif

    <section class="lc-card p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-bold text-slate-800">Historial de consumos por orden</h2>
            <span class="text-xs text-slate-500">{{ $historialConsumos->count() }} evento(s) registrados</span>
        </div>

        <form method="GET" action="{{ route('produccion.seguimiento', $ordenView->id) }}" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="lc-field xl:col-span-2">
                <label class="lc-label">Material</label>
                <select name="material_id" class="lc-select lc-input-soft">
                    <option value="">Todos</option>
                    @foreach ($materialesFiltro as $material)
                        <option value="{{ $material->id }}" @selected((string) $filtros->material_id === (string) $material->id)>{{ $material->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Registró</label>
                <select name="usuario_id" class="lc-select lc-input-soft">
                    <option value="">Todos</option>
                    @foreach ($usuariosFiltro as $usuario)
                        <option value="{{ $usuario->id }}" @selected((string) $filtros->usuario_id === (string) $usuario->id)>{{ $usuario->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Estado material</label>
                <select name="estado_material" class="lc-select lc-input-soft">
                    <option value="">Todos</option>
                    <option value="Conforme" @selected($filtros->estado_material === 'Conforme')>Conforme</option>
                    <option value="No Conforme" @selected($filtros->estado_material === 'No Conforme')>No Conforme</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Desde</label>
                <input type="date" name="desde" value="{{ $filtros->desde }}" class="lc-input lc-input-soft">
            </div>

            <div class="lc-field">
                <label class="lc-label">Hasta</label>
                <input type="date" name="hasta" value="{{ $filtros->hasta }}" class="lc-input lc-input-soft">
            </div>

            <div class="xl:col-span-6 flex flex-wrap justify-end gap-2 pt-1">
                <a href="{{ route('produccion.seguimiento', $ordenView->id) }}" class="lc-btn-secondary">Limpiar</a>
                <button type="submit" class="lc-btn-primary">Filtrar historial</button>
            </div>
        </form>

        @if ($historialConsumos->isEmpty())
            <div class="lc-empty-state py-8">
                <div class="lc-empty-title">Sin consumos registrados</div>
                <p class="lc-empty-copy">Cuando registres material en el Paso 4, aparecerá aquí la línea de tiempo con detalle por evento.</p>
            </div>
        @else
            <div class="lc-scrollbar overflow-x-auto rounded-2xl border border-slate-200">
                <table class="lc-table min-w-[920px]">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Material</th>
                            <th>Usada</th>
                            <th>Merma</th>
                            <th>Estado</th>
                            <th>Registró</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historialConsumos as $evento)
                            <tr>
                                <td class="text-[11px] text-slate-500">
                                    {{ optional($evento->fecha)->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="font-semibold text-slate-700">{{ $evento->material ?: 'Material sin nombre' }}</td>
                                <td>{{ number_format($evento->cantidad_usada, 2) }}</td>
                                <td class="{{ $evento->cantidad_merma > 0 ? 'text-amber-700 font-semibold' : 'text-slate-500' }}">
                                    {{ number_format($evento->cantidad_merma, 2) }}
                                </td>
                                <td>
                                    <span class="{{ $evento->cantidad_merma > 0 ? 'lc-badge lc-badge-warning' : 'lc-badge lc-badge-success' }}">
                                        {{ $evento->estado_material }}
                                    </span>
                                </td>
                                <td class="text-slate-600">{{ $evento->usuario ?: 'Sistema' }}</td>
                                <td class="max-w-[320px] text-[11px] text-slate-500">{{ $evento->observaciones ?: 'Sin observaciones' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
