<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_producto', function (Blueprint $table) {
            $table->decimal('stock_minimo_terminado', 15, 4)
                ->default(5)
                ->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_producto', function (Blueprint $table) {
            $table->dropColumn('stock_minimo_terminado');
        });
    }
};
