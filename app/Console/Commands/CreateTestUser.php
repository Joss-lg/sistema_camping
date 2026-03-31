<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    protected $signature = 'user:create {--name=Roman Ramos} {--email=roman@gmail.com} {--password=!123456}';

    protected $description = 'Create a test user account';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        // Verificar si ya existe
        if (User::where('email', $email)->exists()) {
            $this->error("El usuario con email $email ya existe.");
            return 1;
        }

        // Obtener el primer rol disponible
        $role = \App\Models\Role::first();

        if (!$role) {
            $this->error('No hay roles disponibles en la base de datos.');
            return 1;
        }

        // Crear el usuario
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $role->id,
            'activo' => true,
        ]);

        $this->info("✅ Usuario creado exitosamente:");
        $this->info("   ID: {$user->id}");
        $this->info("   Nombre: {$user->name}");
        $this->info("   Email: {$user->email}");
        $this->info("   Rol: {$role->nombre}");
        $this->info("\n📧 Credenciales de acceso:");
        $this->info("   Email: {$email}");
        $this->info("   Contraseña: {$password}");

        return 0;
    }
}
