<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventario_productos_terminados')
            ->where('estado', 'Vendido')
            ->update(['estado' => 'Terminado']);
    }

    public function down(): void
    {
        DB::table('inventario_productos_terminados')
            ->where('estado', 'Terminado')
            ->update(['estado' => 'Vendido']);
    }
};
