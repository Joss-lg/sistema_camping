<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $proveedoresConDuplicados = DB::table('contactos_proveedores')
            ->select('proveedor_id')
            ->where('es_contacto_principal', true)
            ->groupBy('proveedor_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('proveedor_id');

        foreach ($proveedoresConDuplicados as $proveedorId) {
            $idPrincipal = DB::table('contactos_proveedores')
                ->where('proveedor_id', $proveedorId)
                ->where('es_contacto_principal', true)
                ->orderBy('id')
                ->value('id');

            if (! $idPrincipal) {
                continue;
            }

            DB::table('contactos_proveedores')
                ->where('proveedor_id', $proveedorId)
                ->where('es_contacto_principal', true)
                ->where('id', '!=', $idPrincipal)
                ->update([
                    'es_contacto_principal' => false,
                    'updated_at' => now(),
                ]);
        }

        Schema::table('contactos_proveedores', function (Blueprint $table): void {
            $table->index(['proveedor_id', 'es_contacto_principal'], 'idx_contacto_proveedor_principal');
        });
    }

    public function down(): void
    {
        Schema::table('contactos_proveedores', function (Blueprint $table): void {
            $table->dropIndex('idx_contacto_proveedor_principal');
        });
    }
};
