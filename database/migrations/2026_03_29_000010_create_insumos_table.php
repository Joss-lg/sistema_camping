<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();

            // Business identity
            $table->string('codigo_insumo', 30)->unique()->index();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->text('especificaciones_tecnicas')->nullable();

            // Categorization
            $table->foreignId('categoria_insumo_id')
                ->constrained('categorias_insumo')
                ->restrictOnDelete();
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();
            $table->foreignId('tipo_producto_id')
                ->nullable()
                ->constrained('tipos_producto')
                ->nullOnDelete();

            // Stock management - all in decimal (15,4) for precision
            $table->decimal('stock_minimo', 15, 4)->default(0);
            $table->decimal('stock_actual', 15, 4)->default(0)->index();
            $table->decimal('stock_reservado', 15, 4)->default(0);

            // Supplier relationship
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();
            $table->string('codigo_proveedor_insumo', 50)->nullable();

            // Pricing
            $table->decimal('precio_unitario', 15, 4);
            $table->decimal('precio_costo', 15, 4)->nullable();

            // Default location
            $table->foreignId('ubicacion_almacen_id')
                ->nullable()
                ->constrained('ubicaciones_almacen')
                ->restrictOnDelete();

            // Status
            $table->string('estado', 30)->default('Activo')->index();
            $table->boolean('activo')->default(true)->index();

            // Metadata
            $table->string('unidad_compra', 30)->default('pz');
            $table->integer('cantidad_minima_orden')->default(1);
            $table->string('imagen_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['categoria_insumo_id', 'activo']);
            $table->index(['proveedor_id', 'activo']);
            $table->index(['tipo_producto_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
