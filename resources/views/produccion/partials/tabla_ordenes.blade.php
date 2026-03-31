@forelse ($ordenes as $orden)
    @php
        $estado = strtoupper($orden->estado->nombre ?? 'PENDIENTE');
        $badgeStyles = match($estado) {
            'EN_PROCESO' => 'bg-blue-100 text-blue-800 border-blue-200',
            'FINALIZADA' => 'bg-green-100 text-green-800 border-green-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    @endphp
    <tr class="hover:bg-slate-50/50 transition-colors">
        <td class="p-4 font-mono text-slate-400">#{{ $orden->id }}</td>
        <td class="p-4">
            <div class="font-bold text-slate-800">{{ $orden->producto?->nombre ?? '-' }}</div>
            <div class="text-[10px] text-slate-400 font-mono">{{ $orden->producto?->sku ?? '' }}</div>
        </td>
        <td class="p-4">
            <div class="text-xs font-medium text-slate-700">
                {{ number_format($orden->cantidad_completada, 2) }} / {{ number_format($orden->cantidad, 2) }}
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                @php $porc = min(100, max(0, ($orden->cantidad > 0 ? ($orden->cantidad_completada / $orden->cantidad) * 100 : 0))); @endphp
                <div class="h-full bg-green-500 rounded-full" style="width: {{ $porc }}%"></div>
            </div>
        </td>
        <td class="p-4">
            <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold {{ $badgeStyles }}">
                {{ $estado }}
            </span>
        </td>
        <td class="p-4 text-xs text-slate-700">
            <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 font-semibold">
                {{ $orden->etapa_fabricacion_actual ?? 'Corte' }}
            </span>
        </td>
        <td class="p-4 text-slate-600">{{ $orden->responsable?->nombre ?? '-' }}</td>
        <td class="p-4 text-[11px] leading-tight text-slate-600">
            <div><span class="font-semibold">Máquina:</span> {{ $orden->maquina_asignada ?: 'Sin asignar' }}</div>
            <div class="mt-1"><span class="font-semibold">Turno:</span> {{ $orden->turno_asignado ?: 'Sin turno' }}</div>
        </td>
        <td class="p-4 text-[11px] leading-tight text-slate-500">
            <div><span class="font-semibold uppercase text-[9px] text-slate-400">Inicio:</span> {{ optional($orden->fecha_inicio)->format('d/m/y H:i') ?? '-' }}</div>
            <div class="mt-1"><span class="font-semibold uppercase text-[9px] text-slate-400">Esper:</span> {{ optional($orden->fecha_esperada)->format('d/m/y H:i') ?? '-' }}</div>
        </td>
        <td class="p-4">
            @if ($orden->usosMaterial->isEmpty())
                <span class="text-slate-300 italic text-xs">Sin registros</span>
            @else
                @php $mermaAlta = (float) ($orden->merma_porcentaje ?? 0) >= 8; @endphp
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2 space-y-2 max-h-32 overflow-y-auto shadow-inner">
                    <div class="text-[10px] font-semibold {{ $mermaAlta ? 'text-red-700' : 'text-slate-600' }}">
                        Merma: {{ number_format((float) ($orden->merma_total ?? 0), 2) }} ({{ number_format((float) ($orden->merma_porcentaje ?? 0), 2) }}%)
                    </div>
                    @foreach ($orden->usosMaterial as $uso)
                        <div class="text-[10px] border-b border-slate-100 last:border-0 pb-1">
                            <div class="font-bold text-slate-700">{{ $uso->material?->nombre }}</div>
                            <div class="flex justify-between text-slate-500">
                                <span>Uso: {{ number_format($uso->cantidad_usada, 2) }}</span>
                                @if($uso->cantidad_merma > 0)
                                    <span class="text-amber-600">M: {{ number_format($uso->cantidad_merma, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </td>
        @if ($canManage)
            <td class="p-4 bg-slate-50/50">
                <div class="flex flex-col gap-1.5">
                    @if ($orden->bloqueada_aprobacion)
                        <div class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                            Bloqueada por aprobacion pendiente
                            @if($orden->etapa_pendiente_aprobacion)
                                : {{ $orden->etapa_pendiente_aprobacion }}
                            @endif
                        </div>
                    @endif
                    <form method="POST" action="{{ route('produccion.update-estado', $orden->id) }}" class="flex flex-col gap-1.5">
                        @csrf
                        @method('PATCH')
                        <select name="etapa_fabricacion_actual" class="text-[10px] p-1 border border-slate-300 rounded bg-white" @disabled($orden->bloqueada_aprobacion)>
                            @foreach (['Corte', 'Costura', 'Ensamblado', 'Acabado'] as $etapaFabricacion)
                                <option value="{{ $etapaFabricacion }}" @selected(($orden->etapa_fabricacion_actual ?? 'Corte') === $etapaFabricacion)>{{ strtoupper($etapaFabricacion) }}</option>
                            @endforeach
                        </select>
                        <select name="estado" class="text-[10px] p-1 border border-slate-300 rounded bg-white" @disabled($orden->bloqueada_aprobacion)>
                            <option value="PENDIENTE" @selected($estado === 'PENDIENTE')>PENDIENTE</option>
                            <option value="EN_PROCESO" @selected($estado === 'EN_PROCESO')>EN PROCESO</option>
                            <option value="FINALIZADA" @selected($estado === 'FINALIZADA')>FINALIZADA</option>
                        </select>
                        <input type="number" name="cantidad_completada" min="0" step="0.01" 
                            value="{{ number_format($orden->cantidad_completada, 2, '.', '') }}"
                            class="text-[10px] p-1 border border-slate-300 rounded focus:ring-1 focus:ring-green-400 outline-none"
                            @disabled($orden->bloqueada_aprobacion)
                            placeholder="Finalizada">
                        <button type="submit" class="bg-slate-800 text-white text-[9px] font-bold py-1 rounded hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @disabled($orden->bloqueada_aprobacion)>ACTUALIZAR</button>
                    </form>

                    <form method="POST" action="{{ route('produccion.update-asignacion', $orden->id) }}" class="flex flex-col gap-1.5">
                        @csrf
                        @method('PATCH')
                        <select name="responsable_id" class="text-[10px] p-1 border border-slate-300 rounded bg-white" required>
                            <option value="">Operario</option>
                            @foreach ($usuarios ?? [] as $usuario)
                                <option value="{{ $usuario->id }}" @selected((string) $orden->responsable?->id === (string) $usuario->id)>{{ $usuario->nombre }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="maquina_asignada" value="{{ $orden->maquina_asignada }}" placeholder="Máquina" class="text-[10px] p-1 border border-slate-300 rounded">
                        <select name="turno_asignado" class="text-[10px] p-1 border border-slate-300 rounded bg-white">
                            <option value="">Sin turno</option>
                            <option value="Manana" @selected(($orden->turno_asignado ?? '') === 'Manana')>Mañana</option>
                            <option value="Tarde" @selected(($orden->turno_asignado ?? '') === 'Tarde')>Tarde</option>
                            <option value="Noche" @selected(($orden->turno_asignado ?? '') === 'Noche')>Noche</option>
                        </select>
                        <button type="submit" class="bg-indigo-600 text-white text-[9px] font-bold py-1 rounded hover:bg-indigo-700 transition-colors">ASIGNAR TAREA</button>
                    </form>
                    
                    @if ($estado !== 'FINALIZADA' && $estado !== 'CANCELADA')
                        <form method="POST" action="{{ route('produccion.cancelar', $orden->id) }}" 
                              onsubmit="return confirm('¿Deseas cancelar esta orden? Se liberarán todos los materiales reservados.')">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 text-white text-[9px] font-bold py-1 rounded hover:bg-red-700 transition-colors">CANCELAR</button>
                        </form>
                    @endif
                </div>
            </td>
        @endif
    </tr>
@empty
    <tr><td colspan="10" class="text-center text-slate-400 py-6">Sin órdenes registradas.</td></tr>
@endforelse
