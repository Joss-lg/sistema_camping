<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_insumo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_padre_id')->nullable()->constrained('categorias_insumo')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('categoria_padre_id');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_insumo');
    }
};
