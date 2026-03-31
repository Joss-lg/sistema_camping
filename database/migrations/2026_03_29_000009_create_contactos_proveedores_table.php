<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->string('nombre_completo');
            $table->string('cargo', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_movil', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('es_contacto_principal')->default(false);
            $table->timestamps();

            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_proveedores');
    }
};
