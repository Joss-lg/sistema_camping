<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumos_materiales', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('orden_produccion_id')
                ->constrained('ordenes_produccion')
                ->cascadeOnDelete();

            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();

            $table->foreignId('lote_insumo_id')
                ->constrained('lotes_insumos')
                ->restrictOnDelete();

            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Consumption
            $table->decimal('cantidad_consumida', 15, 4);
            $table->decimal('cantidad_desperdicio', 15, 4)->default(0);

            // User tracking
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('fecha_consumo')->useCurrent()->index();

            // Quality tracking
            $table->string('estado_material', 50)->default('Conforme')->index();
            $table->text('observaciones')->nullable();
            $table->boolean('requiere_revision')->default(false);

            // Metadata
            $table->string('numero_lote_produccion', 100)->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['orden_produccion_id', 'fecha_consumo'], 'idx_cons_orden_fecha');
            $table->index(['lote_insumo_id', 'fecha_consumo'], 'idx_cons_lote_fecha');
            $table->index(['user_id', 'fecha_consumo'], 'idx_cons_user_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumos_materiales');
    }
};
