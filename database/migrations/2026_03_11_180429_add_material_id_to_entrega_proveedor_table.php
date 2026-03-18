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
        Schema::table('entrega_proveedor', function (Blueprint $table) {
            $table->foreignId('material_id')->nullable()->after('orden_compra_id')
                ->constrained('material')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrega_proveedor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
        });
    }
};
