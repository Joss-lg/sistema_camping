<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones_almacen', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ubicacion', 20)->unique();
            $table->string('nombre', 100);
            $table->string('tipo', 50);
            $table->string('edificio', 50)->nullable();
            $table->string('piso', 20)->nullable();
            $table->string('seccion', 50)->nullable();
            $table->string('estante', 20)->nullable();
            $table->string('nivel', 20)->nullable();
            $table->decimal('capacidad_maxima', 15, 4)->nullable();
            $table->decimal('capacidad_actual', 15, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo_ubicacion');
            $table->index('tipo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_almacen');
    }
};
