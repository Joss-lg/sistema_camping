<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_produccion', function (Blueprint $table) {
            $table->string('etapa_fabricacion_actual', 40)
                ->default('Corte')
                ->after('estado');
            $table->string('maquina_asignada', 120)
                ->nullable()
                ->after('etapa_fabricacion_actual');
            $table->string('turno_asignado', 30)
                ->nullable()
                ->after('maquina_asignada');

            $table->index('etapa_fabricacion_actual', 'idx_orden_etapa_fabricacion');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_produccion', function (Blueprint $table) {
            $table->dropIndex('idx_orden_etapa_fabricacion');
            $table->dropColumn(['etapa_fabricacion_actual', 'maquina_asignada', 'turno_asignado']);
        });
    }
};
