<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trazabilidad_etapas', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('orden_produccion_id')
                ->constrained('ordenes_produccion')
                ->cascadeOnDelete();

            $table->foreignId('etapa_plantilla_id')
                ->constrained('etapas_produccion_plantilla')
                ->restrictOnDelete();

            // Execution sequence
            $table->integer('numero_secuencia');
            $table->integer('numero_ejecucion')->default(1);

            // Dates - full precision
            $table->timestamp('fecha_inicio_prevista');
            $table->timestamp('fecha_fin_prevista');
            $table->timestamp('fecha_inicio_real')->nullable();
            $table->timestamp('fecha_fin_real')->nullable();

            // Duration tracking
            $table->integer('duracion_real_minutos')->nullable();
            $table->integer('duracion_estimada_minutos')->default(0);
            $table->decimal('variacion_porcentaje', 5, 2)->nullable();

            // Status - string, NOT enum
            $table->string('estado', 50)->default('Pendiente')->index();
            // Values: 'Pendiente', 'En Proceso', 'Completada', 'Con Defecto', 'Rechazada', 'En Pausa'

            // Assignment
            $table->integer('cantidad_operarios')->default(1);
            $table->text('operarios_asignados')->nullable();

            // Quality notes
            $table->text('observaciones_etapa')->nullable();
            $table->string('resultado_validacion', 50)->nullable();
            $table->text('notas_validacion')->nullable();

            // Metadata
            $table->text('notas_produccion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: garantiza una ejecucion de cada etapa por orden
            $table->unique(['orden_produccion_id', 'etapa_plantilla_id', 'numero_ejecucion'], 'uniq_etapa_ejecucion');

            // Indexes
            $table->index(['orden_produccion_id', 'estado'], 'idx_traz_orden_estado');
            $table->index(['etapa_plantilla_id', 'estado'], 'idx_traz_etapa_estado');
            $table->index(['fecha_inicio_real', 'estado'], 'idx_traz_fecha_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trazabilidad_etapas');
    }
};
