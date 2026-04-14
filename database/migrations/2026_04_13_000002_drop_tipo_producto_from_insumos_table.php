<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            if (Schema::hasColumn('insumos', 'tipo_producto_id')) {
                $table->dropConstrainedForeignId('tipo_producto_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insumos', function (Blueprint $table) {
            if (! Schema::hasColumn('insumos', 'tipo_producto_id')) {
                $table->foreignId('tipo_producto_id')
                    ->nullable()
                    ->after('unidad_medida_id')
                    ->constrained('tipos_producto')
                    ->nullOnDelete();

                $table->index(['tipo_producto_id', 'activo']);
            }
        });
    }
};
