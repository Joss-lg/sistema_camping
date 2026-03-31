<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSistema;
use Illuminate\Database\Seeder;

class ConfiguracionSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'clave' => 'empresa.nombre',
                'valor' => 'Camping Logistics S.A.',
                'tipo_dato' => 'string',
                'categoria' => 'empresa',
                'descripcion' => 'Nombre comercial de la empresa',
                'es_publica' => true,
                'editable' => true,
                'orden_visualizacion' => 1,
                'activo' => true,
            ],
            [
                'clave' => 'empresa.moneda',
                'valor' => 'USD',
                'tipo_dato' => 'string',
                'categoria' => 'empresa',
                'descripcion' => 'Moneda principal de operación',
                'es_publica' => true,
                'editable' => true,
                'orden_visualizacion' => 2,
                'activo' => true,
            ],
            [
                'clave' => 'inventario.stock_minimo_alerta_activa',
                'valor' => 'true',
                'tipo_dato' => 'boolean',
                'categoria' => 'inventario',
                'descripcion' => 'Habilita alertas cuando un insumo cae por debajo del stock mínimo',
                'es_publica' => false,
                'editable' => true,
                'orden_visualizacion' => 10,
                'activo' => true,
            ],
            [
                'clave' => 'produccion.requiere_aprobacion_calidad',
                'valor' => 'true',
                'tipo_dato' => 'boolean',
                'categoria' => 'produccion',
                'descripcion' => 'Exigir aprobación de calidad para liberar producto terminado',
                'es_publica' => false,
                'editable' => true,
                'orden_visualizacion' => 20,
                'activo' => true,
            ],
            [
                'clave' => 'reportes.retencion_dias',
                'valor' => '30',
                'tipo_dato' => 'integer',
                'categoria' => 'reportes',
                'descripcion' => 'Días de retención para archivos de reportes generados',
                'es_publica' => false,
                'editable' => true,
                'orden_visualizacion' => 30,
                'activo' => true,
            ],
        ];

        foreach ($items as $item) {
            ConfiguracionSistema::updateOrCreate(
                ['clave' => $item['clave']],
                $item
            );
        }
    }
}
