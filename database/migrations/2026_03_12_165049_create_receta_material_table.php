<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('receta_material', function (Blueprint $table) {
        $table->id();
        $table->foreignId('producto_id')->constrained('producto_terminado')->cascadeOnDelete();
        $table->foreignId('material_id')->constrained('material')->restrictOnDelete();
        $table->decimal('cantidad_base', 12, 4);
        $table->decimal('merma_porcentaje', 5, 2)->default(0);
        $table->boolean('activo')->default(true);
        $table->timestamps();

        $table->unique(['producto_id', 'material_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('receta_material');
}
};
