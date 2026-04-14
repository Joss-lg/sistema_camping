@extends('layouts.app')

@section('content')
<div class="lc-page space-y-6">
    <div class="lc-page-header">
        <div>
            <div class="lc-kicker">Control de recepción</div>
            <h1 class="lc-title mt-2">Evaluación de calidad de materiales</h1>
            <p class="lc-subtitle mt-2 max-w-3xl">Valida cada material recibido con criterios de estándar y registra evidencia de aceptación, observación o rechazo.</p>
        </div>
        <a href="{{ route('entregas.index') }}" class="lc-btn-secondary">Volver a entregas</a>
    </div>

    @if (session('ok'))
        <div class="lc-alert lc-alert-success">{{ session('ok') }}</div>
    @endif

    @if ($errors->any())
        <div class="lc-alert lc-alert-danger">
            <div class="font-semibold">No se pudo guardar la evaluación.</div>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <article class="lc-stat-card">
            <div class="lc-stat-label">Entradas registradas</div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ $stats->total_entradas }}</div>
        </article>
        <article class="lc-stat-card bg-emerald-50/80">
            <div class="lc-stat-label text-emerald-700">Evaluadas</div>
            <div class="mt-2 text-2xl font-black text-emerald-700">{{ $stats->evaluadas }}</div>
        </article>
        <article class="lc-stat-card bg-amber-50/80">
            <div class="lc-stat-label text-amber-700">Pendientes</div>
            <div class="mt-2 text-2xl font-black text-amber-700">{{ $stats->pendientes }}</div>
        </article>
    </section>

    <section class="lc-card p-5">
        <form method="GET" action="{{ route('calidad-material.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="lc-field md:col-span-2">
                <label class="lc-label">Filtrar por resultado</label>
                <select name="resultado" class="lc-select">
                    <option value="">Todos</option>
                    <option value="APROBADO" @selected($resultadoFiltro === 'APROBADO')>APROBADO</option>
                    <option value="OBSERVADO" @selected($resultadoFiltro === 'OBSERVADO')>OBSERVADO</option>
                    <option value="RECHAZADO" @selected($resultadoFiltro === 'RECHAZADO')>RECHAZADO</option>
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="lc-btn-primary">Aplicar filtro</button>
                <a href="{{ route('calidad-material.index') }}" class="lc-btn-secondary">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        @forelse ($registros as $registro)
            @php
                $evaluacion = $registro->evaluacion;
                $bloquearEdicionAprobada = (string) ($evaluacion?->resultado ?? '') === 'APROBADO';
                $badgeResultado = match((string) ($evaluacion?->resultado ?? 'PENDIENTE')) {
                    'APROBADO' => 'lc-badge lc-badge-success',
                    'OBSERVADO' => 'lc-badge lc-badge-warning',
                    'RECHAZADO' => 'lc-badge lc-badge-danger',
                    default => 'lc-badge lc-badge-neutral',
                };
            @endphp

            <article class="lc-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-800">{{ $registro->insumo }}</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Movimiento #{{ $registro->movimiento_id }} · Lote {{ $registro->lote ?: 'Sin lote' }} · Cantidad {{ number_format($registro->cantidad, 2) }}
                        </p>
                        <p class="text-xs text-slate-500">Registrado por {{ $registro->registrado_por }} · {{ optional($registro->fecha_movimiento)->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="{{ $badgeResultado }}">{{ $evaluacion?->resultado ?: 'PENDIENTE' }}</span>
                </div>

                @if ($evaluacion)
                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <p><strong>Inspector:</strong> {{ $evaluacion->user?->name ?: 'N/A' }}</p>
                        <p><strong>Cumplimiento:</strong> {{ number_format((float) $evaluacion->cumplimiento_porcentaje, 2) }}%</p>
                        <p><strong>Observaciones:</strong> {{ $evaluacion->observaciones ?: 'Sin observaciones' }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('calidad-material.store') }}" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                    @csrf
                    <input type="hidden" name="movimiento_inventario_id" value="{{ $registro->movimiento_id }}">

                    @if ($bloquearEdicionAprobada)
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            Esta evaluación ya fue aprobada y no puede modificarse.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @foreach ($criteriosEstandar as $criterio)
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" name="criterios[{{ $criterio }}]" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked(data_get(old('criterios', []), $criterio) === '1') {{ $bloquearEdicionAprobada ? 'disabled' : '' }}>
                                {{ str_replace('_', ' ', strtoupper($criterio)) }}
                            </label>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="lc-field">
                            <label class="lc-label">Resultado</label>
                            <select name="resultado" required class="lc-select" {{ $bloquearEdicionAprobada ? 'disabled' : '' }}>
                                <option value="">Selecciona</option>
                                <option value="APROBADO" @selected(old('resultado') === 'APROBADO')>APROBADO</option>
                                <option value="OBSERVADO" @selected(old('resultado') === 'OBSERVADO')>OBSERVADO</option>
                                <option value="RECHAZADO" @selected(old('resultado') === 'RECHAZADO')>RECHAZADO</option>
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Estado actual de lote</label>
                            <input type="text" class="lc-input lc-input-soft" value="{{ $registro->estado_lote }}" disabled>
                        </div>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Observaciones</label>
                        <textarea name="observaciones" rows="2" class="lc-input" placeholder="Describe hallazgos, desviaciones o evidencia..." {{ $bloquearEdicionAprobada ? 'disabled' : '' }}>{{ old('observaciones') }}</textarea>
                    </div>

                    <div>
                        @if (! $bloquearEdicionAprobada)
                            <button type="submit" class="lc-btn-primary">Guardar evaluación</button>
                        @else
                            <span class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-500">
                                Evaluación bloqueada por aprobación
                            </span>
                        @endif
                    </div>
                </form>
            </article>
        @empty
            <div class="lc-empty-state py-10">
                <div class="lc-empty-title">Sin entradas de materiales</div>
                <p class="lc-empty-copy">Registra recepciones en Entregas para habilitar evaluaciones de calidad.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
