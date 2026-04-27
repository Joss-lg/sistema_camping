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
        Schema::table('trazabilidad_etapas', function (Blueprint $table) {
            $table->timestamp('fecha_inicio_prevista')->nullable()->change();
            $table->timestamp('fecha_fin_prevista')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trazabilidad_etapas', function (Blueprint $table) {
            $table->timestamp('fecha_inicio_prevista')->nullable(false)->change();
            $table->timestamp('fecha_fin_prevista')->nullable(false)->change();
        });
    }
};
