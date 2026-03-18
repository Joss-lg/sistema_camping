<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uso_material', function (Blueprint $table) {
            $table->decimal('cantidad_merma', 12, 2)->default(0)->after('cantidad_usada');
            $table->string('motivo_merma', 255)->nullable()->after('cantidad_merma');
        });
    }

    public function down(): void
    {
        Schema::table('uso_material', function (Blueprint $table) {
            $table->dropColumn(['cantidad_merma', 'motivo_merma']);
        });
    }
};
