<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_terminados', function (Blueprint $table) {
            $table->string('codigo_qr', 255)
                ->nullable()
                ->after('codigo_barras');
        });
    }

    public function down(): void
    {
        Schema::table('productos_terminados', function (Blueprint $table) {
            $table->dropColumn('codigo_qr');
        });
    }
};
