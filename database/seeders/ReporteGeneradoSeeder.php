<?php

namespace Database\Seeders;

use App\Models\ReporteGenerado;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReporteGeneradoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereIn('email', [
            'admin@correo.com',
            'admin@camping.local',
            'admin@logicamp.local',
        ])->first();

        $items = [
            [
                'codigo_reporte' => 'REP-ENTREGAS-20260329-001',
                'nombre_reporte' => 'Entregas por periodo',
                'tipo_reporte' => 'entregas',
                'formato' => 'csv',
                'parametros' => [
                    'from' => now()->subDays(7)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'ruta_archivo' => 'storage/reportes/entregas-20260329.csv',
                'generado_por_user_id' => $admin?->id,
                'fecha_desde' => now()->subDays(7)->toDateString(),
                'fecha_hasta' => now()->toDateString(),
                'total_registros' => 12,
                'tamano_bytes' => 18764,
                'estado' => 'Generado',
                'expiracion_at' => now()->addDays(30),
            ],
            [
                'codigo_reporte' => 'REP-PRODUCCION-20260329-001',
                'nombre_reporte' => 'Producción diaria',
                'tipo_reporte' => 'produccion',
                'formato' => 'csv',
                'parametros' => [
                    'from' => now()->subDays(1)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'ruta_archivo' => 'storage/reportes/produccion-20260329.csv',
                'generado_por_user_id' => $admin?->id,
                'fecha_desde' => now()->subDays(1)->toDateString(),
                'fecha_hasta' => now()->toDateString(),
                'total_registros' => 8,
                'tamano_bytes' => 12090,
                'estado' => 'Descargado',
                'expiracion_at' => now()->addDays(30),
            ],
        ];

        foreach ($items as $item) {
            ReporteGenerado::updateOrCreate(
                ['codigo_reporte' => $item['codigo_reporte']],
                $item
            );
        }
    }
}
