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
        $superAdminRole = Role::query()->updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'nombre' => 'Super Administrador',
                'descripcion' => 'Acceso total al sistema y configuraciones avanzadas',
                'nivel_acceso' => 100,
            ]
        );

        // Usuario inicial de administración
        User::updateOrCreate(
            ['email' => 'admin@correo.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin123456'),
                'role_id' => $superAdminRole->id,
                'activo' => true,
                'telefono' => '+52-55-1234-5678',
                'departamento' => 'Dirección General',
                'ultimo_acceso' => now(),
            ]
        );
    }
}
