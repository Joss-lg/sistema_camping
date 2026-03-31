<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trazabilidad_registros', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('trazabilidad_etapa_id')
                ->constrained('trazabilidad_etapas')
                ->cascadeOnDelete();

            $table->foreignId('orden_produccion_id')
                ->constrained('ordenes_produccion')
                ->cascadeOnDelete();

            // User tracking
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Event type - string, NOT enum
            $table->string('tipo_evento', 50)->index();
            // Values: 'Inicio', 'Pausa', 'Reanudacion', 'Cambio Estado', 'Observacion', 'Rechazo', 'Aprobacion', 'Defecto Detectado'

            // Event data
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50)->nullable();
            $table->text('descripcion_evento');
            $table->text('detalles_cambio')->nullable();

            // Timestamp
            $table->timestamp('fecha_evento')->useCurrent()->index();

            // Additional metadata
            $table->integer('duracion_actividad_minutos')->nullable();
            $table->string('dispositivo_registro', 100)->nullable();
            $table->boolean('requiere_seguimiento')->default(false);

            // Metadata
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes para queries comunes
            $table->index(['orden_produccion_id', 'fecha_evento'], 'idx_reg_orden_fecha');
            $table->index(['trazabilidad_etapa_id', 'tipo_evento'], 'idx_reg_etapa_evento');
            $table->index(['user_id', 'fecha_evento'], 'idx_reg_user_fecha');
            $table->index(['tipo_evento', 'fecha_evento'], 'idx_reg_evento_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trazabilidad_registros');
    }
};
