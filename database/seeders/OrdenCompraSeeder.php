<?php

namespace Database\Seeders;

use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Insumo;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OrdenCompraSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Get references
        $admin = User::whereIn('email', [
            'admin@correo.com',
            'admin@logicamp.local',
            'admin@camping.local',
        ])->first();
        $proveedorTMT = Proveedor::where('codigo_proveedor', 'PROV-001')->first();
        $proveedorMEN = Proveedor::where('codigo_proveedor', 'PROV-002')->first();

        $unidadMetro = UnidadMedida::where('abreviatura', 'm')->first();
        $unidadPz = UnidadMedida::where('abreviatura', 'pz')->first();

        if (!$admin || !$proveedorTMT || !$proveedorMEN) {
            return;
        }

        $ordenesCompra = [
            [
                'numero_orden' => 'OC-2026-001',
                'proveedor_id' => $proveedorTMT->id,
                'user_id' => $admin->id,
                'fecha_orden' => Carbon::now(),
                'fecha_entrega_prevista' => Carbon::now()->addDays(15),
                'estado' => 'Pendiente',
                'total_items' => 2,
                'total_cantidad' => 300,
                'subtotal' => 13665.0,
                'impuestos' => 2186.4,
                'descuentos' => 683.25,
                'costo_flete' => 500.0,
                'monto_total' => 15668.15,
                'condiciones_pago' => 'Neto 30',
                'incoterm' => 'FOB',
                'detalles' => [
                    [
                        'numero_linea' => 1,
                        'insumo_codigo' => 'INS-001',
                        'cantidad_solicitada' => 150,
                        'precio_unitario' => 45.5,
                        'subtotal' => 6825.0,
                        'fecha_entrega_esperada_linea' => Carbon::now()->addDays(15),
                    ],
                    [
                        'numero_linea' => 2,
                        'insumo_codigo' => 'INS-002',
                        'cantidad_solicitada' => 150,
                        'precio_unitario' => 25.75,
                        'subtotal' => 3862.5,
                        'fecha_entrega_esperada_linea' => Carbon::now()->addDays(15),
                    ],
                ],
            ],
            [
                'numero_orden' => 'OC-2026-002',
                'proveedor_id' => $proveedorMEN->id,
                'user_id' => $admin->id,
                'fecha_orden' => Carbon::now()->subDays(5),
                'fecha_entrega_prevista' => Carbon::now()->addDays(5),
                'estado' => 'Confirmada',
                'total_items' => 2,
                'total_cantidad' => 370,
                'subtotal' => 4205.0,
                'impuestos' => 672.8,
                'descuentos' => 147.175,
                'costo_flete' => 300.0,
                'monto_total' => 5030.625,
                'condiciones_pago' => 'Neto 15',
                'incoterm' => 'FOB',
                'detalles' => [
                    [
                        'numero_linea' => 1,
                        'insumo_codigo' => 'INS-003',
                        'cantidad_solicitada' => 200,
                        'precio_unitario' => 8.5,
                        'subtotal' => 1700.0,
                        'fecha_entrega_esperada_linea' => Carbon::now()->addDays(5),
                    ],
                    [
                        'numero_linea' => 2,
                        'insumo_codigo' => 'INS-004',
                        'cantidad_solicitada' => 150,
                        'precio_unitario' => 12.5,
                        'subtotal' => 1875.0,
                        'fecha_entrega_esperada_linea' => Carbon::now()->addDays(5),
                    ],
                ],
            ],
        ];

        foreach ($ordenesCompra as $ordenData) {
            $detalles = $ordenData['detalles'];
            unset($ordenData['detalles']);

            $orden = OrdenCompra::updateOrCreate(
                ['numero_orden' => $ordenData['numero_orden']],
                $ordenData
            );

            // Crear detalles
            foreach ($detalles as $detalle) {
                $insumo = Insumo::where('codigo_insumo', $detalle['insumo_codigo'])->first();
                $unidadMedida = $unidadMetro;

                if ($insumo && $unidadMedida) {
                    OrdenCompraDetalle::updateOrCreate(
                        [
                            'orden_compra_id' => $orden->id,
                            'numero_linea' => $detalle['numero_linea'],
                        ],
                        [
                            'insumo_id' => $insumo->id,
                            'unidad_medida_id' => $unidadMedida->id,
                            'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                            'precio_unitario' => $detalle['precio_unitario'],
                            'subtotal' => $detalle['subtotal'],
                            'fecha_entrega_esperada_linea' => $detalle['fecha_entrega_esperada_linea'],
                            'estado_linea' => 'Pendiente',
                        ]
                    );
                }
            }
        }
    }
}
