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
            $table->string('estado_revision', 30)->default('PENDIENTE')->after('estado_calidad');
            $table->text('observacion_revision')->nullable()->after('observaciones');
            $table->foreignId('revisado_por_usuario_id')->nullable()->after('observacion_revision')
                ->constrained('usuario')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('revisado_en')->nullable()->after('revisado_por_usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrega_proveedor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revisado_por_usuario_id');
            $table->dropColumn('revisado_en');
            $table->dropColumn('observacion_revision');
            $table->dropColumn('estado_revision');
        });
    }
};
