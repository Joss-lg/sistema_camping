<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('nivel_acceso')->default(1);
            $table->timestamps();

            $table->index('slug');
            $table->index('nivel_acceso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
