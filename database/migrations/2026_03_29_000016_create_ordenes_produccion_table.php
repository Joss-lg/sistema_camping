<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_produccion', function (Blueprint $table) {
            $table->id();

            // Order identification
            $table->string('numero_orden', 50)->unique()->index();
            $table->foreignId('tipo_producto_id')
                ->constrained('tipos_producto')
                ->restrictOnDelete();

            // User tracking
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Dates
            $table->timestamp('fecha_orden')->useCurrent();
            $table->date('fecha_inicio_prevista');
            $table->date('fecha_fin_prevista');
            $table->date('fecha_inicio_real')->nullable();
            $table->date('fecha_fin_real')->nullable();

            // Quantities - decimal(15,4)
            $table->decimal('cantidad_produccion', 15, 4);
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Status - string, NOT enum
            $table->string('estado', 50)->default('Pendiente')->index();
            // Values: 'Pendiente', 'En Proceso', 'Completada', 'Cancelada', 'En Pausa'

            // Progress tracking
            $table->integer('etapas_totales')->default(0);
            $table->integer('etapas_completadas')->default(0);
            $table->decimal('porcentaje_completado', 5, 2)->default(0)->index();

            // Financial
            $table->decimal('costo_estimado', 15, 4)->default(0);
            $table->decimal('costo_real', 15, 4)->default(0)->nullable();

            // Metadata
            $table->text('notas')->nullable();
            $table->string('prioridad', 20)->default('Normal')->index();
            $table->boolean('requiere_calidad')->default(true);
            $table->text('especificaciones_especiales')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['tipo_producto_id', 'estado'], 'idx_orden_producto_estado');
            $table->index(['user_id', 'fecha_orden'], 'idx_orden_user_fecha');
            $table->index(['estado', 'fecha_orden'], 'idx_orden_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_produccion');
    }
};
