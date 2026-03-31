<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            // Movement type - string, NOT enum
            $table->string('tipo_movimiento', 50)->index();
            // Values: 'Entrada', 'Salida', 'Ajuste', 'Consumo', 'Traspaso', 'Devolución'

            // References
            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();

            // Optional lote reference
            $table->foreignId('lote_insumo_id')
                ->nullable()
                ->constrained('lotes_insumos')
                ->restrictOnDelete();

            // Order references (at least one can be set, but not both required)
            $table->foreignId('orden_compra_id')
                ->nullable()
                ->constrained('ordenes_compra')
                ->setOnDelete('set null');

            // orden_produccion_id will be added in Phase 5 (Production module)
            // For now, store the reference as string/nullable
            $table->unsignedBigInteger('orden_produccion_id')->nullable();
            // Will add foreign key constraint in Phase 5 migration

            // Quantities - decimal (15,4)
            $table->decimal('cantidad', 15, 4);
            $table->foreignId('unidad_medida_id')
                ->constrained('unidades_medida')
                ->restrictOnDelete();

            // Location tracking
            $table->foreignId('ubicacion_origen_id')
                ->nullable()
                ->constrained('ubicaciones_almacen')
                ->setOnDelete('set null');

            $table->foreignId('ubicacion_destino_id')
                ->nullable()
                ->constrained('ubicaciones_almacen')
                ->setOnDelete('set null');

            // Document reference
            $table->string('referencia_documento', 100)->nullable()->index();
            $table->string('motivo', 100)->nullable();

            // User & timestamp
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('fecha_movimiento')->useCurrent()->index();

            // Metadata
            $table->text('notas')->nullable();
            $table->string('numero_lote_produccion', 100)->nullable();
            $table->decimal('saldo_anterior', 15, 4)->nullable();
            $table->decimal('saldo_posterior', 15, 4)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for frequent queries
            $table->index(['insumo_id', 'fecha_movimiento']);
            $table->index(['lote_insumo_id', 'tipo_movimiento']);
            $table->index(['tipo_movimiento', 'fecha_movimiento']);
            $table->index(['orden_compra_id']);
            $table->index(['orden_produccion_id']);
            $table->index(['user_id', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
