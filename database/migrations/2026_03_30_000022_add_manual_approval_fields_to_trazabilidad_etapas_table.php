<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trazabilidad_etapas', function (Blueprint $table): void {
            $table->foreignId('responsable_id')
                ->nullable()
                ->after('etapa_plantilla_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_aprobacion')
                ->nullable()
                ->after('fecha_fin_real');

            $table->foreignId('aprobado_por')
                ->nullable()
                ->after('fecha_aprobacion')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['estado', 'responsable_id'], 'idx_traz_estado_responsable');
        });
    }

    public function down(): void
    {
        Schema::table('trazabilidad_etapas', function (Blueprint $table): void {
            $table->dropIndex('idx_traz_estado_responsable');
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn('fecha_aprobacion');
            $table->dropConstrainedForeignId('responsable_id');
        });
    }
};
