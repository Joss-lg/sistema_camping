<?php

namespace App\Services;

use App\Models\ConsumoMaterial;
use App\Models\Insumo;
use App\Models\LoteInsumo;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduccionConsumoService
{
    public function registrarConsumo(
        OrdenProduccion $orden,
        Insumo $material,
        OrdenProduccionMaterial $lineaMaterial,
        LoteInsumo $lote,
        float $cantidadUsada,
        float $cantidadMerma,
        float $total,
        ?string $tipoMerma,
        ?string $motivoMerma
    ): void {
        DB::transaction(function () use ($orden, $material, $lineaMaterial, $lote, $cantidadUsada, $cantidadMerma, $total, $tipoMerma, $motivoMerma): void {
            $tipoMerma = (string) ($tipoMerma ?? 'Otro');
            $observaciones = $motivoMerma;

            if ($cantidadMerma > 0) {
                $observaciones = trim(sprintf('[%s] %s', $tipoMerma, (string) $observaciones));
            }

            ConsumoMaterial::query()->create([
                'orden_produccion_id' => $orden->id,
                'insumo_id' => $material->id,
                'lote_insumo_id' => $lote->id,
                'unidad_medida_id' => $material->unidad_medida_id,
                'cantidad_consumida' => $cantidadUsada,
                'cantidad_desperdicio' => $cantidadMerma,
                'user_id' => Auth::id() ?? $orden->user_id,
                'fecha_consumo' => now(),
                'estado_material' => $cantidadMerma > 0 ? 'No Conforme' : 'Conforme',
                'observaciones' => $observaciones,
                'requiere_revision' => $cantidadMerma > 0,
                'numero_lote_produccion' => $orden->numero_orden,
            ]);

            $material->stock_actual = max(0, (float) $material->stock_actual - $total);
            $material->save();

            $lote->cantidad_consumida = (float) $lote->cantidad_consumida + $total;
            $lote->cantidad_en_stock = max(0, (float) $lote->cantidad_en_stock - $total);
            $lote->save();

            $lineaMaterial->cantidad_utilizada = (float) $lineaMaterial->cantidad_utilizada + $cantidadUsada;
            $lineaMaterial->cantidad_desperdicio = (float) $lineaMaterial->cantidad_desperdicio + $cantidadMerma;

            $consumoAcumulado = (float) $lineaMaterial->cantidad_utilizada + (float) $lineaMaterial->cantidad_desperdicio;
            $lineaMaterial->estado_asignacion = $consumoAcumulado >= (float) $lineaMaterial->cantidad_necesaria
                ? 'Consumido'
                : 'Parcial';
            $lineaMaterial->save();
        });
    }
}
