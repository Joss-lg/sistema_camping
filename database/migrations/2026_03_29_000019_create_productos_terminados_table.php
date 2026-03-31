<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_terminados', function (Blueprint $table) {
            $table->id();

            // Product identification
            $table->string('numero_lote_produccion', 100)->unique()->index();
            $table->string('numero_serie', 150)->nullable()->unique();

            // Source
            $table->foreignId('orden_produccion_id')
                ->constrained('ordenes_produccion')
                ->restrictOnDelete();

            $table->foreignId('tipo_producto_id')
                ->constrained('tipos_producto')
                ->restrictOnDelete();

            // User tracking
            $table->foreignId('user_responsable_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Dates
            $table->timestamp('fecha_produccion')->useCurrent();
            $table->timestamp('fecha_finalizacion')->nullable();
            $table->timestamp('fecha_empaque')->nullable();

            // Quantities - decimal(15,4)
            $table->decimal('cantidad_producida', 15, 4);
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Status - string, NOT enum
            $table->string('estado', 50)->default('Producido')->index();
            // Values: 'Producido', 'Control Calidad Pendiente', 'Aprobado', 'Rechazado', 'Empacado'

            // Quality
            $table->string('estado_calidad', 50)->default('Pendiente Inspección')->index();
            $table->text('observaciones_calidad')->nullable();
            $table->foreignId('user_inspeccion_id')
                ->nullable()
                ->constrained('users')
                ->setOnDelete('set null');

            $table->timestamp('fecha_inspeccion')->nullable();

            // Cost tracking
            $table->decimal('costo_produccion', 15, 4)->default(0);

            // Metadata
            $table->text('notas')->nullable();
            $table->string('codigo_barras', 100)->nullable();
            $table->string('imagen_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['orden_produccion_id', 'estado'], 'idx_prod_orden_estado');
            $table->index(['tipo_producto_id', 'fecha_produccion'], 'idx_prod_tipo_fecha');
            $table->index(['estado_calidad', 'fecha_inspeccion'], 'idx_prod_calidad_insp');
            $table->index(['user_responsable_id', 'fecha_produccion'], 'idx_prod_user_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_terminados');
    }
};
