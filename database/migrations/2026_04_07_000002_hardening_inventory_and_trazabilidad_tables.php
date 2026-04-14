<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize legacy/alternate status values to the canonical domain value.
        DB::table('trazabilidad_etapas')
            ->where('estado', 'Completada')
            ->update(['estado' => 'Finalizada']);

        // Clear orphan references before adding FK.
        DB::table('movimientos_inventario')
            ->whereNotNull('orden_produccion_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ordenes_produccion')
                    ->whereColumn('ordenes_produccion.id', 'movimientos_inventario.orden_produccion_id');
            })
            ->update(['orden_produccion_id' => null]);

        Schema::table('movimientos_inventario', function (Blueprint $table): void {
            $table->foreign('orden_produccion_id', 'fk_mov_inv_orden_prod')
                ->references('id')
                ->on('ordenes_produccion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table): void {
            $table->dropForeign('fk_mov_inv_orden_prod');
        });
    }
};
