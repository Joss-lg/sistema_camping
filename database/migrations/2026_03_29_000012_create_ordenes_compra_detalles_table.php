<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra_detalles', function (Blueprint $table) {
            $table->id();

            // Order reference
            $table->foreignId('orden_compra_id')
                ->constrained('ordenes_compra')
                ->cascadeOnDelete();

            // Line item number
            $table->integer('numero_linea');

            // Product reference
            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Quantities - all decimal (15,4)
            $table->decimal('cantidad_solicitada', 15, 4);
            $table->decimal('cantidad_recibida', 15, 4)->default(0);
            $table->decimal('cantidad_aceptada', 15, 4)->default(0);

            // Pricing - all decimal (15,4)
            $table->decimal('precio_unitario', 15, 4);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 4);

            // Expected delivery
            $table->string('lote_esperado', 100)->nullable();
            $table->date('fecha_entrega_esperada_linea')->nullable();

            // Status
            $table->string('estado_linea', 50)->default('Pendiente')->index();

            // Notes
            $table->text('notas')->nullable();
            $table->text('notas_recepcion')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: garantiza una linea por item por orden
            $table->unique(['orden_compra_id', 'numero_linea']);

            // Indexes
            $table->index(['orden_compra_id', 'estado_linea']);
            $table->index(['insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_detalles');
    }
};
