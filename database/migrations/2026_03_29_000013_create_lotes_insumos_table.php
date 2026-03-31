<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_insumos', function (Blueprint $table) {
            $table->id();

            // Lote identification
            $table->string('numero_lote', 100)->unique()->index();
            $table->string('lote_proveedor', 100)->nullable();

            // References
            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();
            $table->foreignId('orden_compra_id')
                ->constrained('ordenes_compra')
                ->restrictOnDelete();
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();

            // Dates
            $table->date('fecha_lote');
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamp('fecha_recepcion')->useCurrent();

            // Quantities - all decimal (15,4)
            $table->decimal('cantidad_recibida', 15, 4);
            $table->decimal('cantidad_en_stock', 15, 4);
            $table->decimal('cantidad_consumida', 15, 4)->default(0);
            $table->decimal('cantidad_rechazada', 15, 4)->default(0);

            // Location
            $table->foreignId('ubicacion_almacen_id')
                ->constrained('ubicaciones_almacen')
                ->restrictOnDelete();

            // Quality control
            $table->string('estado_calidad', 50)->default('Pendiente')->index();
            $table->string('numero_certificado', 100)->nullable();
            $table->text('observaciones_calidad')->nullable();

            // Tracking
            $table->string('numero_contenedor', 100)->nullable();
            $table->string('numero_referencia', 100)->nullable();
            $table->foreignId('user_recepcion_id')
                ->nullable()
                ->constrained('users')
                ->setOnDelete('set null');

            // Metadata
            $table->text('notas')->nullable();
            $table->string('numero_certificado_origen', 100)->nullable();
            $table->boolean('requiere_inspeccion')->default(false);
            $table->boolean('activo')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['insumo_id', 'estado_calidad']);
            $table->index(['orden_compra_id', 'estado_calidad']);
            $table->index(['fecha_vencimiento', 'activo']);
            $table->index(['ubicacion_almacen_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_insumos');
    }
};
