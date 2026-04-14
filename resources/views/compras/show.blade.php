@extends('layouts.app')

@section('content')
@php
    $volverAEntregas = request()->query('origen') === 'entregas';
@endphp
<div class="container mx-auto px-4 py-6 space-y-6">
    @if (session('ok'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('ok') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">No se pudo guardar la evaluación.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Detalle de orden de compra</h1>
            <p class="text-slate-500 text-sm mt-1">Resumen de la orden #{{ $ordenCompra->id }} y proveedor asociado.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ordenes-compra.pdf', $ordenCompra) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2h-3.586a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0010.586 2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Descargar PDF
            </a>
            <a href="{{ route('ordenes-compra.edit', $ordenCompra) }}" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar
            </a>
            <a href="{{ $volverAEntregas ? route('entregas.index') : route('ordenes-compra.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
        </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
        <h2 class="text-lg font-bold text-slate-800 mb-6">Información general</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Número de orden</dt>
                <dd class="text-2xl font-bold text-slate-900 mt-2">#{{ $ordenCompra->numero_orden }}</dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Estado</dt>
                <dd class="mt-2">
                    @if($ordenCompra->estado === 'PENDIENTE')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-amber-600"></span>PENDIENTE
                        </span>
                    @elseif($ordenCompra->estado === 'CONFIRMADA')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>CONFIRMADA
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>{{ $ordenCompra->estado }}
                        </span>
                    @endif
                </dd>
            </div>
            <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl p-6">
                <dt class="text-slate-600 text-xs font-semibold uppercase tracking-wider">Proveedor asignado</dt>
                <dd class="text-lg font-semibold text-slate-900 mt-2">{{ $ordenCompra->proveedor?->razon_social ?? 'Sin asignar' }}</dd>
            </div>
        </dl>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Evaluación de calidad de materiales</h2>
                <p class="text-slate-500 text-sm mt-1">Registra la evaluación de las entradas de inventario asociadas a esta orden.</p>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs font-semibold">
                <div class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Entradas: {{ $statsCalidad->total_entradas }}</div>
                <div class="rounded-lg bg-emerald-100 px-3 py-2 text-emerald-700">Evaluadas: {{ $statsCalidad->evaluadas }}</div>
                <div class="rounded-lg bg-amber-100 px-3 py-2 text-amber-700">Pendientes: {{ $statsCalidad->pendientes }}</div>
            </div>
        </div>

        @forelse ($registrosCalidad as $registro)
            @php
                $evaluacion = $registro->evaluacion;
                $bloquearEdicionAprobada = (string) ($evaluacion?->resultado ?? '') === 'APROBADO';
                $badgeResultado = match((string) ($evaluacion?->resultado ?? 'PENDIENTE')) {
                    'APROBADO' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'OBSERVADO' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'RECHAZADO' => 'bg-rose-100 text-rose-700 border-rose-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
            @endphp

            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">{{ $registro->insumo }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Movimiento #{{ $registro->movimiento_id }} · Lote {{ $registro->lote ?: 'Sin lote' }} · Cantidad {{ number_format($registro->cantidad, 2) }}</p>
                        <p class="text-xs text-slate-500">Registrado por {{ $registro->registrado_por }} · {{ optional($registro->fecha_movimiento)->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $badgeResultado }}">{{ $evaluacion?->resultado ?: 'PENDIENTE' }}</span>
                </div>

                @if ($evaluacion)
                    <div class="rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-600">
                        <p><strong>Inspector:</strong> {{ $evaluacion->user?->name ?: 'N/A' }}</p>
                        <p><strong>Cumplimiento:</strong> {{ number_format((float) $evaluacion->cumplimiento_porcentaje, 2) }}%</p>
                        <p><strong>Observaciones:</strong> {{ $evaluacion->observaciones ?: 'Sin observaciones' }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('calidad-material.store') }}" class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                    @csrf
                    <input type="hidden" name="movimiento_inventario_id" value="{{ $registro->movimiento_id }}">
                    <input type="hidden" name="redirect_orden_compra_id" value="{{ $ordenCompra->id }}">

                    @if ($bloquearEdicionAprobada)
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            Esta evaluación ya fue aprobada y no puede modificarse.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @foreach ($criteriosEstandar as $criterio)
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" name="criterios[{{ $criterio }}]" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600" @checked((string) data_get(old('criterios', []), $criterio, '0') === '1') {{ ($bloquearEdicionAprobada || ! $canEvaluarCalidad) ? 'disabled' : '' }}>
                                {{ str_replace('_', ' ', strtoupper($criterio)) }}
                            </label>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Resultado</label>
                            <select name="resultado" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" {{ ($bloquearEdicionAprobada || ! $canEvaluarCalidad) ? 'disabled' : '' }}>
                                <option value="">Selecciona</option>
                                <option value="APROBADO" @selected(old('resultado') === 'APROBADO')>APROBADO</option>
                                <option value="OBSERVADO" @selected(old('resultado') === 'OBSERVADO')>OBSERVADO</option>
                                <option value="RECHAZADO" @selected(old('resultado') === 'RECHAZADO')>RECHAZADO</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Estado actual de lote</label>
                            <input type="text" value="{{ $registro->estado_lote }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Observaciones</label>
                        <textarea name="observaciones" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Describe hallazgos, desviaciones o evidencia..." {{ ($bloquearEdicionAprobada || ! $canEvaluarCalidad) ? 'disabled' : '' }}>{{ old('observaciones') }}</textarea>
                    </div>

                    <div>
                        @if (! $canEvaluarCalidad)
                            <span class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-500">Sin permisos para evaluar calidad</span>
                        @elseif (! $bloquearEdicionAprobada)
                            <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar evaluación</button>
                        @else
                            <span class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-500">Evaluación bloqueada por aprobación</span>
                        @endif
                    </div>
                </form>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                Esta orden no tiene movimientos de entrada para evaluar.
            </div>
        @endforelse
    </section>
</div>
@endsection
