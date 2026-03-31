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
        ])->get()->keyBy('slug');

        if ($roles->count() < 4) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@correo.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin123456'),
                'role_id' => $roles['super_admin']->id,
                'activo' => true,
                'telefono' => $faker->numerify('+52-55-####-####'),
                'departamento' => 'Dirección General',
                'ultimo_acceso' => now(),
            ]
        );
    }
}
