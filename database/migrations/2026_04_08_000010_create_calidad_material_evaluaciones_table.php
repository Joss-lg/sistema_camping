<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calidad_material_evaluaciones', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('movimiento_inventario_id')
                ->constrained('movimientos_inventario')
                ->cascadeOnDelete();

            $table->foreignId('lote_insumo_id')
                ->nullable()
                ->constrained('lotes_insumos')
                ->setNullOnDelete();

            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('resultado', 30)->index();
            $table->json('criterios')->nullable();
            $table->decimal('cumplimiento_porcentaje', 5, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_evaluacion')->useCurrent()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['insumo_id', 'fecha_evaluacion'], 'idx_cme_insumo_fecha');
            $table->index(['resultado', 'fecha_evaluacion'], 'idx_cme_resultado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calidad_material_evaluaciones');
    }
};
