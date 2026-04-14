<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones_almacen', function (Blueprint $table) {
            if (Schema::hasColumn('ubicaciones_almacen', 'edificio')) {
                $table->dropColumn('edificio');
            }

            if (Schema::hasColumn('ubicaciones_almacen', 'piso')) {
                $table->dropColumn('piso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones_almacen', function (Blueprint $table) {
            if (! Schema::hasColumn('ubicaciones_almacen', 'edificio')) {
                $table->string('edificio', 50)->nullable()->after('tipo');
            }

            if (! Schema::hasColumn('ubicaciones_almacen', 'piso')) {
                $table->string('piso', 20)->nullable()->after('edificio');
            }
        });
    }
};
