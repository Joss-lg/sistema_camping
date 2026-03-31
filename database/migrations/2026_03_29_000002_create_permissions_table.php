<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('modulo', 50);
            $table->boolean('puede_ver')->default(false);
            $table->boolean('puede_crear')->default(false);
            $table->boolean('puede_editar')->default(false);
            $table->boolean('puede_eliminar')->default(false);
            $table->boolean('puede_aprobar')->default(false);
            $table->timestamps();

            $table->index('role_id');
            $table->index(['role_id', 'modulo']);
            $table->unique(['role_id', 'modulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
