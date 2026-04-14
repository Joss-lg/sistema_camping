<?php

namespace Database\Seeders;

use App\Models\NotificacionSistema;
use App\Models\User;
use App\Services\PermisoService;
use Illuminate\Database\Seeder;

class NotificacionSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereIn('email', [
            'admin@correo.com',
            'admin@camping.local',
            'admin@logicamp.local',
        ])->first();
        $rolAdmin = PermisoService::resolveRoleByInput('SUPER_ADMIN')
            ?? PermisoService::resolveRoleByInput('ADMIN');

        $items = [
            [
                'titulo' => 'Revisión diaria de inventario',
                'mensaje' => 'Existen insumos por debajo del stock mínimo. Revisar módulo de Insumos.',
                'tipo' => 'Advertencia',
                'modulo' => 'Insumos',
                'prioridad' => 'Alta',
                'user_id' => $admin?->id,
                'role_id' => $rolAdmin?->id,
                'estado' => 'Pendiente',
                'fecha_programada' => now(),
                'requiere_accion' => true,
                'url_accion' => '/insumos',
                'metadata' => ['canal' => 'sistema', 'origen' => 'seeder'],
            ],
            [
                'titulo' => 'Órdenes de producción pendientes',
                'mensaje' => 'Hay órdenes pendientes de iniciar para hoy.',
                'tipo' => 'Info',
                'modulo' => 'Produccion',
                'prioridad' => 'Media',
                'user_id' => $admin?->id,
                'role_id' => null,
                'estado' => 'Pendiente',
                'fecha_programada' => now(),
                'requiere_accion' => true,
                'url_accion' => '/produccion',
                'metadata' => ['canal' => 'sistema', 'origen' => 'seeder'],
            ],
            [
                'titulo' => 'Reporte semanal disponible',
                'mensaje' => 'El reporte semanal de producción se generó correctamente.',
                'tipo' => 'Exito',
                'modulo' => 'Reportes',
                'prioridad' => 'Baja',
                'user_id' => $admin?->id,
                'role_id' => $rolAdmin?->id,
                'estado' => 'Leida',
                'fecha_programada' => now()->subDay(),
                'fecha_leida' => now()->subDay()->addHours(2),
                'enviada_at' => now()->subDay(),
                'requiere_accion' => false,
                'metadata' => ['canal' => 'sistema', 'origen' => 'seeder'],
            ],
        ];

        foreach ($items as $item) {
            NotificacionSistema::updateOrCreate(
                [
                    'titulo' => $item['titulo'],
                    'modulo' => $item['modulo'],
                    'user_id' => $item['user_id'],
                ],
                $item
            );
        }
    }
}
