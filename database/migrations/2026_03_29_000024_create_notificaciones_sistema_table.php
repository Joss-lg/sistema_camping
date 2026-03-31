<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_sistema', function (Blueprint $table) {
            $table->id();

            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->string('tipo', 30)->default('Info');
            // Valores ejemplo: Info, Exito, Advertencia, Error

            $table->string('modulo', 50)->nullable();
            $table->string('prioridad', 20)->default('Media');
            // Valores ejemplo: Baja, Media, Alta, Critica

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->nullOnDelete();

            $table->string('estado', 20)->default('Pendiente');
            // Valores ejemplo: Pendiente, Leida, Archivada, Expirada

            $table->timestamp('fecha_programada')->nullable();
            $table->timestamp('fecha_leida')->nullable();
            $table->timestamp('enviada_at')->nullable();

            $table->boolean('requiere_accion')->default(false);
            $table->string('url_accion')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_programada'], 'idx_notif_estado_fecha');
            $table->index(['tipo', 'prioridad'], 'idx_notif_tipo_prioridad');
            $table->index(['user_id', 'estado'], 'idx_notif_user_estado');
            $table->index(['role_id', 'estado'], 'idx_notif_role_estado');
            $table->index(['modulo'], 'idx_notif_modulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_sistema');
    }
};
