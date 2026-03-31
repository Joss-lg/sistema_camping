<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_sistema', function (Blueprint $table) {
            $table->id();

            $table->string('clave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo_dato', 30)->default('string');
            // Valores ejemplo: string, integer, decimal, boolean, json

            $table->string('categoria', 50)->default('general');
            $table->text('descripcion')->nullable();

            $table->boolean('es_publica')->default(false);
            $table->boolean('editable')->default(true);
            $table->integer('orden_visualizacion')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['categoria', 'activo'], 'idx_conf_categoria_activo');
            $table->index(['es_publica', 'activo'], 'idx_conf_publica_activo');
            $table->index(['orden_visualizacion'], 'idx_conf_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_sistema');
    }
};
