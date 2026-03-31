<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_proveedor', 20)->unique();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('tipo_proveedor', 50);

            // Dirección
            $table->text('direccion')->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('pais', 100)->default('México');

            // Contacto
            $table->string('telefono_principal', 20)->nullable();
            $table->string('email_general')->nullable();
            $table->string('sitio_web')->nullable();

            // Términos comerciales
            $table->unsignedInteger('dias_credito')->default(0);
            $table->decimal('limite_credito', 15, 4)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);

            // Calificación
            $table->decimal('calificacion', 3, 2)->default(0);
            $table->string('estatus', 30)->default('Activo');

            $table->text('certificaciones')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('codigo_proveedor');
            $table->index('tipo_proveedor');
            $table->index('estatus');
            $table->index('razon_social');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
