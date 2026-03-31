<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_produccion_materiales', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('orden_produccion_id')
                ->constrained('ordenes_produccion')
                ->cascadeOnDelete();

            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();

            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // BOM quantities - decimal(15,4)
            $table->decimal('cantidad_necesaria', 15, 4);
            $table->decimal('cantidad_utilizada', 15, 4)->default(0);
            $table->decimal('cantidad_desperdicio', 15, 4)->default(0);

            // Allocation
            $table->string('estado_asignacion', 50)->default('Pendiente')->index();
            // Values: 'Pendiente', 'Asignado', 'Consumido', 'Parcial'

            // Location & lote hints
            $table->text('notas_asignacion')->nullable();
            $table->integer('numero_linea')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: una linea por insumo por orden
            $table->unique(['orden_produccion_id', 'insumo_id'], 'uniq_orden_insumo');

            // Indexes
            $table->index(['orden_produccion_id', 'estado_asignacion'], 'idx_mat_orden_estado');
            $table->index(['insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_produccion_materiales');
    }
};
