<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();

            // Order identification
            $table->string('numero_orden', 50)->unique()->index();
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();

            // User tracking
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Dates
            $table->timestamp('fecha_orden')->useCurrent();
            $table->date('fecha_entrega_prevista');
            $table->date('fecha_entrega_real')->nullable();

            // Status - string field, NOT enum
            $table->string('estado', 50)->default('Pendiente')->index();

            // Quantities
            $table->integer('total_items')->default(0);
            $table->decimal('total_cantidad', 15, 4)->default(0);

            // Financial - all decimal (15,4)
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('impuestos', 15, 4)->default(0);
            $table->decimal('descuentos', 15, 4)->default(0);
            $table->decimal('costo_flete', 15, 4)->default(0);
            $table->decimal('monto_total', 15, 4)->default(0)->index();

            // References
            $table->string('numero_folio_proveedor', 100)->nullable();
            $table->string('numero_contenedor', 100)->nullable();
            $table->string('numero_awb', 100)->nullable();

            // Metadata
            $table->text('notas')->nullable();
            $table->string('condiciones_pago', 100)->nullable();
            $table->string('incoterm', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['proveedor_id', 'estado']);
            $table->index(['user_id', 'fecha_orden']);
            $table->index(['estado', 'fecha_orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
