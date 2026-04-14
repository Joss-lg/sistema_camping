<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_productos_terminados', function (Blueprint $table) {
            $table->id();

            // Product reference
            $table->foreignId('producto_terminado_id')
                ->constrained('productos_terminados')
                ->cascadeOnDelete();

            $table->foreignId('tipo_producto_id')
                ->constrained('tipos_producto')
                ->restrictOnDelete();

            // Location
            $table->foreignId('ubicacion_almacen_id')
                ->constrained('ubicaciones_almacen')
                ->restrictOnDelete();

            // Inventory quantities - decimal(15,4)
            $table->decimal('cantidad_en_almacen', 15, 4);
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Reservation tracking
            $table->decimal('cantidad_reservada', 15, 4)->default(0);

            // Dates
            $table->date('fecha_ingreso_almacen');
            $table->date('fecha_vencimiento')->nullable();

            // Status
            $table->string('estado', 50)->default('En Almacén')->index();
            // Values: 'En Almacén', 'Reservado', 'Enviado', 'Terminado', 'Descartado'

            // Valuation
            $table->decimal('precio_unitario', 15, 4)->default(0);
            $table->decimal('valor_total_inventario', 15, 4)->default(0);

            // Metadata
            $table->text('notas')->nullable();
            $table->boolean('requiere_inspeccion_periodica')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: un registro por producto por ubicación
            $table->unique(['producto_terminado_id', 'ubicacion_almacen_id'], 'uniq_inv_producto_ubicacion');

            // Indexes
            $table->index(['tipo_producto_id', 'estado'], 'idx_inv_tipo_estado');
            $table->index(['ubicacion_almacen_id', 'estado'], 'idx_inv_ubicacion_estado');
            $table->index(['fecha_vencimiento'], 'idx_inv_vencimiento');
            $table->index(['estado', 'fecha_ingreso_almacen'], 'idx_inv_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_productos_terminados');
    }
};
