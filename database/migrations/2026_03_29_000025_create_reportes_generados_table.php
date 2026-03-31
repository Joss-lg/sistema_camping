<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_generados', function (Blueprint $table) {
            $table->id();

            $table->string('codigo_reporte', 80)->unique();
            $table->string('nombre_reporte', 120);
            $table->string('tipo_reporte', 50);
            $table->string('formato', 20)->default('csv');

            $table->json('parametros')->nullable();
            $table->string('ruta_archivo')->nullable();

            $table->foreignId('generado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->integer('total_registros')->default(0);
            $table->bigInteger('tamano_bytes')->nullable();

            $table->string('estado', 20)->default('Generado');
            // Valores ejemplo: Generado, Descargado, Expirado, Error

            $table->timestamp('expiracion_at')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_reporte', 'created_at'], 'idx_rep_tipo_creado');
            $table->index(['generado_por_user_id', 'created_at'], 'idx_rep_user_creado');
            $table->index(['estado', 'created_at'], 'idx_rep_estado_creado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_generados');
    }
};
