<?php

namespace App\Services;

use App\Models\OrdenProduccion;
use App\Models\ProductoTerminado;

class IdentificacionProductoService
{
    public static function generarNumeroSerie(int $ordenId, int $tipoProductoId): string
    {
        do {
            $codigo = sprintf(
                'SER-%s-TP%03d-OP%05d-%04d',
                now()->format('Ymd'),
                $tipoProductoId,
                $ordenId,
                random_int(1, 9999)
            );
        } while (ProductoTerminado::query()->where('numero_serie', $codigo)->exists());

        return $codigo;
    }

    public static function generarCodigoBarras(int $ordenId, int $tipoProductoId): string
    {
        do {
            $codigo = sprintf(
                'BAR%s%03d%05d%03d',
                now()->format('ymdHis'),
                $tipoProductoId,
                $ordenId,
                random_int(100, 999)
            );
        } while (ProductoTerminado::query()->where('codigo_barras', $codigo)->exists());

        return $codigo;
    }

    public static function generarCodigoQr(string $lote, string $numeroSerie): string
    {
        return sprintf('LOGICAMP|LOTE:%s|SERIE:%s|TS:%s', $lote, $numeroSerie, now()->format('YmdHis'));
    }

    public static function generarSkuVisual(OrdenProduccion $orden): string
    {
        $slug = strtoupper((string) ($orden->tipoProducto?->slug ?: 'TERM'));
        return sprintf('%s-%s-%04d', $slug, now()->format('ymd'), $orden->id);
    }
}
