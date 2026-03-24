<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('orden_produccion', function (Blueprint $table) {
            $table->unsignedBigInteger('responsable_id')->nullable()->after('usuario_id');
            $table->foreign('responsable_id')->references('id')->on('usuario');
        });
    }

    public function down()
    {
        Schema::table('orden_produccion', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->dropColumn('responsable_id');
        });
    }
};
