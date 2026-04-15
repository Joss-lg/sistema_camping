<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proveedores') || ! Schema::hasColumn('proveedores', 'codigo_proveedor')) {
            return;
        }

        foreach (['proveedores_codigo_proveedor_unique', 'proveedores_codigo_proveedor_index'] as $indexName) {
            try {
                Schema::table('proveedores', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            } catch (\Throwable) {
                // Ignorar si el índice ya no existe en el entorno actual.
            }
        }

        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('codigo_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores', 'codigo_proveedor')) {
                $table->string('codigo_proveedor', 50)->nullable()->after('id');
                $table->unique('codigo_proveedor', 'proveedores_codigo_proveedor_unique');
                $table->index('codigo_proveedor', 'proveedores_codigo_proveedor_index');
            }
        });
    }
};
