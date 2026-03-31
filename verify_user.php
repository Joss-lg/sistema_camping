<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'roman@gmail.com')->first();
if ($user) {
    echo "✅ Usuario creado exitosamente:\n";
    echo "   ID: {$user->id}\n";
    echo "   Nombre: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Activo: " . ($user->activo ? 'Sí' : 'No') . "\n";
    echo "\n📧 Credenciales de acceso:\n";
    echo "   Email: roman@gmail.com\n";
    echo "   Contraseña: !123456\n";
} else {
    echo "❌ Usuario no encontrado\n";
}
