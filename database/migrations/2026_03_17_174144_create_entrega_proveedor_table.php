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
        if (!Schema::hasTable('entrega_proveedor')) {
            Schema::create('entrega_proveedor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('usuario_id')->constrained('usuario')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('proveedor_id')->constrained('proveedor')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('orden_compra_id')->nullable()->constrained('orden_compra')->cascadeOnUpdate()->nullOnDelete();
                $table->dateTime('fecha_entrega');
                $table->decimal('cantidad_entregada', 12, 2);
                $table->string('estado_calidad', 30);
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega_proveedor');
    }
};
