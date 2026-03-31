<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapas_produccion_plantilla', function (Blueprint $table) {
            $table->id();

            // Template identification
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('codigo', 50)->unique();

            // Product relationship
            $table->foreignId('tipo_producto_id')
                ->constrained('tipos_producto')
                ->restrictOnDelete();

            // Sequence
            $table->integer('numero_secuencia');

            // Duration and resources
            $table->integer('duracion_estimada_minutos')->default(0);
            $table->integer('cantidad_operarios')->default(1);
            $table->text('instrucciones_detalladas')->nullable();

            // Status
            $table->boolean('requiere_validacion')->default(false);
            $table->boolean('es_etapa_critica')->default(false);
            $table->boolean('activo')->default(true)->index();

            // Metadata
            $table->string('tipo_etapa', 50)->default('Manufactura')->index();
            // Values: 'Corte', 'Costura', 'Ensamble', 'Inspección', 'Empaque', 'Manufacturación', etc.

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tipo_producto_id', 'numero_secuencia'], 'idx_etapa_producto_seq');
            $table->index(['tipo_etapa', 'activo'], 'idx_etapa_tipo_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapas_produccion_plantilla');
    }
};
