<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_produccion', function (Blueprint $table) {
            $table->boolean('es_plantilla_bom')
                ->default(false)
                ->after('notas');

            $table->index('es_plantilla_bom', 'idx_ordenes_produccion_es_plantilla_bom');
        });

        DB::table('ordenes_produccion')
            ->whereIn('notas', [
                'Plantilla BOM (no ejecutar)',
                'Orden base generada para gestión BOM.',
            ])
            ->update(['es_plantilla_bom' => true]);
    }

    public function down(): void
    {
        Schema::table('ordenes_produccion', function (Blueprint $table) {
            $table->dropIndex('idx_ordenes_produccion_es_plantilla_bom');
            $table->dropColumn('es_plantilla_bom');
        });
    }
};
