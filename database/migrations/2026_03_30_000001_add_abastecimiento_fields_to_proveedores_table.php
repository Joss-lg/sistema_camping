<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->unsignedSmallInteger('tiempo_entrega_dias')->default(3)->after('dias_credito');
            $table->string('condiciones_pago', 120)->nullable()->after('descuento_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['tiempo_entrega_dias', 'condiciones_pago']);
        });
    }
};
