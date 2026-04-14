@forelse ($ordenes as $orden)
    @php
        $estado = strtoupper($orden->estado->nombre ?? 'PENDIENTE');
        $badgeStyles = match($estado) {
            'EN_PROCESO' => 'lc-badge border-sky-200 bg-sky-50 text-sky-700',
            'FINALIZADA' => 'lc-badge lc-badge-success',
            default => 'lc-badge lc-badge-neutral',
        };
    @endphp
    <tr>
        <td>
            <div class="font-mono text-xs font-semibold text-slate-500">#{{ $orden->id }}</div>
        </td>
        <td>
            <div class="font-bold text-slate-800">{{ $orden->producto?->nombre ?? '-' }}</div>
            <div class="text-[10px] text-slate-400 font-mono">{{ $orden->producto?->sku ?? '' }}</div>
        </td>
        <td>
            <div class="text-xs font-medium text-slate-700">
                {{ number_format($orden->cantidad_completada, 2) }} / {{ number_format($orden->cantidad, 2) }}
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                @php $porc = min(100, max(0, ($orden->cantidad > 0 ? ($orden->cantidad_completada / $orden->cantidad) * 100 : 0))); @endphp
                <div class="h-full bg-green-500 rounded-full" style="width: {{ $porc }}%"></div>
            </div>
        </td>
        <td>
            <span class="{{ $badgeStyles }}">
                {{ $estado }}
            </span>
        </td>
        <td class="text-xs text-slate-700">
            <span class="inline-flex rounded-lg border border-indigo-100 bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-indigo-700">
                {{ $orden->etapa_fabricacion_actual ?? 'Corte' }}
            </span>
        </td>
        <td>{{ $orden->responsable?->nombre ?? '-' }}</td>
        <td class="text-[11px] leading-tight text-slate-600">
            <div><span class="font-semibold">Máquina:</span> {{ $orden->maquina_asignada ?: 'Sin asignar' }}</div>
            <div class="mt-1"><span class="font-semibold">Turno:</span> {{ $orden->turno_asignado ?: 'Sin turno' }}</div>
        </td>
        <td class="text-[11px] leading-tight text-slate-500">
            <div><span class="font-semibold uppercase text-[9px] text-slate-400">Inicio:</span> {{ optional($orden->fecha_inicio)->format('d/m/y H:i') ?? '-' }}</div>
            <div class="mt-1"><span class="font-semibold uppercase text-[9px] text-slate-400">Esper:</span> {{ optional($orden->fecha_esperada)->format('d/m/y H:i') ?? '-' }}</div>
        </td>
        <td>
            @if (($orden->materialesPlanificados ?? collect())->isEmpty() && $orden->usosMaterial->isEmpty())
                <span class="text-slate-400 italic text-xs">Sin registros</span>
            @else
                @php $mermaAlta = (float) ($orden->merma_porcentaje ?? 0) >= 8; @endphp
                <div class="lc-scrollbar max-h-36 space-y-2 overflow-y-auto rounded-xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-2.5 shadow-inner">
                    <div class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $mermaAlta ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-600' }}">
                        Merma: {{ number_format((float) ($orden->merma_total ?? 0), 2) }} ({{ number_format((float) ($orden->merma_porcentaje ?? 0), 2) }}%)
                    </div>

                    @if (($orden->materialesPlanificados ?? collect())->isNotEmpty())
                        @foreach ($orden->materialesPlanificados as $material)
                            <div class="rounded-lg border border-slate-100 bg-white p-1.5 text-[10px] shadow-sm">
                                <div class="font-bold text-slate-700">{{ $material->nombre }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-1.5 py-0.5">Plan: {{ number_format($material->cantidad_planificada, 2) }}</span>
                                    <span class="rounded-full bg-sky-50 px-1.5 py-0.5 text-sky-700">Uso: {{ number_format($material->cantidad_consumida, 2) }}</span>
                                    @if($material->cantidad_merma > 0)
                                        <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-amber-700">M: {{ number_format($material->cantidad_merma, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($orden->usosMaterial as $uso)
                            <div class="rounded-lg border border-slate-100 bg-white p-1.5 text-[10px] shadow-sm">
                                <div class="font-bold text-slate-700">{{ $uso->material?->nombre }}</div>
                                <div class="mt-1 flex items-center gap-1.5 text-slate-500">
                                    <span class="rounded-full bg-sky-50 px-1.5 py-0.5 text-sky-700">Uso: {{ number_format($uso->cantidad_usada, 2) }}</span>
                                    @if($uso->cantidad_merma > 0)
                                        <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-amber-700">M: {{ number_format($uso->cantidad_merma, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif
        </td>
        @if ($canManage)
            <td class="bg-slate-50/80">
                <div class="flex flex-col gap-2">
                    @if ($orden->bloqueada_aprobacion)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 text-[10px] text-amber-700">
                            Bloqueada por aprobacion pendiente
                            @if($orden->etapa_pendiente_aprobacion)
                                : {{ $orden->etapa_pendiente_aprobacion }}
                            @endif
                        </div>
                    @endif

                    @if ($orden->bloqueada_calidad)
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-2 text-[10px] text-rose-700">
                            {{ $orden->motivo_bloqueo_edicion }}
                        </div>
                        <span class="inline-flex cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                            Gestionar orden deshabilitado
                        </span>
                    @else
                        <a href="{{ route('produccion.seguimiento', $orden->id) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.14em] text-indigo-700 transition hover:-translate-y-0.5 hover:bg-indigo-100">
                            Gestionar orden
                        </a>
                    @endif
                    <span class="text-[10px] leading-relaxed text-slate-500">Mover etapa, estado, asignación, consumo e historial en la vista detallada.</span>
                </div>
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ $canManage ? 10 : 9 }}">
            <div class="lc-empty-state my-4">
                <div class="lc-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 21V8.25l3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21m0-12.75 3-2.25 3 2.25V21" />
                    </svg>
                </div>
                <div class="lc-empty-title">Sin órdenes registradas</div>
                <p class="lc-empty-copy">Crea una orden o ajusta el filtro de responsable para volver a poblar el tablero operativo.</p>
            </div>
        </td>
    </tr>
@endforelse
