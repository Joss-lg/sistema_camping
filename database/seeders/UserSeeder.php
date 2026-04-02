<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('es_MX');

        $roles = Role::whereIn('slug', [
            'super_admin',
            'gerente_produccion',
            'supervisor_almacen',
            'operador',
            'proveedor',
        ])->get()->keyBy('slug');

        if ($roles->count() < 5) {
            return;
        }

        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@correo.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin123456'),
                'role_id' => $roles['super_admin']->id,
                'activo' => true,
                'telefono' => '+52-55-1234-5678',
                'departamento' => 'Dirección General',
                'ultimo_acceso' => now(),
            ]
        );

        // Gerente de Producción
        User::updateOrCreate(
            ['email' => 'gerente.produccion@correo.com'],
            [
                'name' => 'Carlos Mendoza',
                'password' => Hash::make('gerente123456'),
                'role_id' => $roles['gerente_produccion']->id,
                'activo' => true,
                'telefono' => '+52-55-2345-6789',
                'departamento' => 'Producción',
                'ultimo_acceso' => now(),
            ]
        );

        // Supervisor de Almacén
        User::updateOrCreate(
            ['email' => 'supervisor.almacen@correo.com'],
            [
                'name' => 'María López',
                'password' => Hash::make('supervisor123456'),
                'role_id' => $roles['supervisor_almacen']->id,
                'activo' => true,
                'telefono' => '+52-55-3456-7890',
                'departamento' => 'Almacén',
                'ultimo_acceso' => now(),
            ]
        );

        // Operador 1
        User::updateOrCreate(
            ['email' => 'operador1@correo.com'],
            [
                'name' => 'Juan Rodríguez',
                'password' => Hash::make('operador123456'),
                'role_id' => $roles['operador']->id,
                'activo' => true,
                'telefono' => '+52-55-4567-8901',
                'departamento' => 'Producción',
                'ultimo_acceso' => now(),
            ]
        );

        // Operador 2
        User::updateOrCreate(
            ['email' => 'operador2@correo.com'],
            [
                'name' => 'Pedro García',
                'password' => Hash::make('operador123456'),
                'role_id' => $roles['operador']->id,
                'activo' => true,
                'telefono' => '+52-55-5678-9012',
                'departamento' => 'Almacén',
                'ultimo_acceso' => now(),
            ]
        );

        // Proveedor de prueba
        User::updateOrCreate(
            ['email' => 'proveedor@correo.com'],
            [
                'name' => 'Javier Proveedor',
                'password' => Hash::make('proveedor123456'),
                'role_id' => $roles['proveedor']->id,
                'activo' => true,
                'telefono' => '+52-55-6789-0123',
                'departamento' => 'Ventas',
                'ultimo_acceso' => now(),
            ]
        );
    }
}
