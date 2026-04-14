<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbastecimientoAutomaticoService
{
    private const ERROR_SIN_RESPONSABLE = 'No existe un usuario activo para asignar ordenes automaticas.';
    private const ERROR_GENERAR_ORDENES_PREFIX = 'Error al generar órdenes: ';
    private const CONDICION_PAGO_FORMAT = 'Credito a %d dias';
    private const PROVEEDOR_ESTATUS_ACTIVO = 'activo';
    /** @var array<int, string> */
    private const OC_ESTADOS_ABIERTOS = [
        OrdenCompra::ESTADO_PENDIENTE,
        OrdenCompra::ESTADO_CONFIRMADA,
    ];

    public function __construct(
        private readonly AbastecimientoResponsableResolver $responsableResolver,
        private readonly AbastecimientoCantidadSugeridaCalculator $cantidadCalculator,
        private readonly AbastecimientoOrdenAbiertaFilter $ordenAbiertaFilter,
        private readonly AbastecimientoOrdenCompraPersister $ordenCompraPersister,
        private readonly AbastecimientoResumenOrdenFactory $resumenOrdenFactory
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generarOrdenes(bool $dryRun = false): array
    {
        try {
            return DB::transaction(function () use ($dryRun): array {
                return $this->generarOrdenesInterno($dryRun);
            }, attempts: 3);
        } catch (\Exception $e) {
            Log::error('AbastecimientoAutomaticoService::generarOrdenes - Error crítico', [
                'error' => $e->getMessage(),
                'clase' => self::class,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'dry_run' => $dryRun,
                'error' => self::ERROR_GENERAR_ORDENES_PREFIX . $e->getMessage(),
                'insumos_detectados' => 0,
                'ordenes_creadas' => 0,
                'detalles_creados' => 0,
                'proveedores_omitidos' => 0,
                'insumos_omitidos_por_orden_abierta' => 0,
                'ordenes' => [],
            ];
        }
    }

    /**
     * Lógica interna para generar órdenes de compra automáticas
     * @return array<string, mixed>
     */
    private function generarOrdenesInterno(bool $dryRun = false): array
    {
        $insumosBajoStock = Insumo::query()
            ->with(['proveedor:id,razon_social,nombre_comercial,estatus,dias_credito,tiempo_entrega_dias,condiciones_pago'])
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->whereNotNull('proveedor_id')
            ->orderBy('proveedor_id')
            ->orderBy('id')
            ->get();

        $resultado = [
            'dry_run' => $dryRun,
            'insumos_detectados' => $insumosBajoStock->count(),
            'ordenes_creadas' => 0,
            'detalles_creados' => 0,
            'proveedores_omitidos' => 0,
            'insumos_omitidos_por_orden_abierta' => 0,
            'ordenes' => [],
        ];

        if ($insumosBajoStock->isEmpty()) {
            return $resultado;
        }

        $responsableId = $this->responsableResolver->resolve();

        if (! $responsableId) {
            $resultado['error'] = self::ERROR_SIN_RESPONSABLE;

            return $resultado;
        }

        $insumosPorProveedor = $insumosBajoStock->groupBy('proveedor_id');

        foreach ($insumosPorProveedor as $proveedorId => $insumosProveedor) {
            $proveedor = $insumosProveedor->first()?->proveedor;

            if (! $proveedor || mb_strtolower((string) $proveedor->estatus) !== self::PROVEEDOR_ESTATUS_ACTIVO) {
                $resultado['proveedores_omitidos']++;
                continue;
            }

            $filtrado = $this->ordenAbiertaFilter->filtrar(
                $insumosProveedor,
                (int) $proveedorId,
                self::OC_ESTADOS_ABIERTOS
            );

            $insumosDisponibles = $filtrado['insumos'];
            $resultado['insumos_omitidos_por_orden_abierta'] += (int) $filtrado['omitidos'];

            if ($insumosDisponibles->isEmpty()) {
                continue;
            }

            $fechaEntrega = now()->addDays(max(1, (int) ($proveedor->tiempo_entrega_dias ?? 3)))->toDateString();
            $condicionesPago = $proveedor->condiciones_pago
                ?: sprintf(self::CONDICION_PAGO_FORMAT, max(0, (int) ($proveedor->dias_credito ?? 0)));

            $lineasOrden = collect();
            $resumenInsumos = [];

            foreach ($insumosDisponibles->values() as $insumo) {
                $cantidadSolicitada = $this->cantidadCalculator->calcular($insumo);
                $precio = (float) ($insumo->precio_costo ?? $insumo->precio_unitario ?? 0);
                $subtotalLinea = round($cantidadSolicitada * $precio, 4);

                $lineasOrden->push([
                    'insumo' => $insumo,
                    'cantidad_solicitada' => $cantidadSolicitada,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotalLinea,
                ]);

                $resumenInsumos[] = $this->resumenOrdenFactory->construirLineaResumen($insumo, $cantidadSolicitada);
            }

            $persistencia = $this->ordenCompraPersister->persistir(
                $dryRun,
                (int) $proveedorId,
                (int) $responsableId,
                $fechaEntrega,
                $condicionesPago,
                $lineasOrden
            );

            $resumenOrden = $this->resumenOrdenFactory->construirResumenOrden(
                (string) ($proveedor->nombre_comercial ?: $proveedor->razon_social),
                $resumenInsumos,
                [
                    'numero_orden' => (string) $persistencia['numero_orden'],
                    'total_cantidad' => (float) $persistencia['total_cantidad'],
                    'subtotal' => (float) $persistencia['subtotal'],
                ]
            );

            if ((bool) $persistencia['orden_creada']) {
                $resultado['ordenes_creadas']++;
                $resultado['detalles_creados'] += (int) $persistencia['detalles_creados'];
            }

            $resultado['ordenes'][] = $resumenOrden;
        }

        return $resultado;
    }
}
