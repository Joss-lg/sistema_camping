<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_insert BEFORE INSERT ON contactos_proveedores FOR EACH ROW BEGIN IF NEW.es_contacto_principal = 1 AND EXISTS (SELECT 1 FROM contactos_proveedores c WHERE c.proveedor_id = NEW.proveedor_id AND c.es_contacto_principal = 1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo puede existir un contacto principal por proveedor'; END IF; END");
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_update BEFORE UPDATE ON contactos_proveedores FOR EACH ROW BEGIN IF NEW.es_contacto_principal = 1 AND EXISTS (SELECT 1 FROM contactos_proveedores c WHERE c.proveedor_id = NEW.proveedor_id AND c.es_contacto_principal = 1 AND c.id <> NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo puede existir un contacto principal por proveedor'; END IF; END");
            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared("CREATE OR REPLACE FUNCTION fn_enforce_single_contacto_principal() RETURNS trigger AS $$ BEGIN IF NEW.es_contacto_principal AND EXISTS (SELECT 1 FROM contactos_proveedores c WHERE c.proveedor_id = NEW.proveedor_id AND c.es_contacto_principal = true AND c.id <> COALESCE(NEW.id, -1)) THEN RAISE EXCEPTION 'Solo puede existir un contacto principal por proveedor'; END IF; RETURN NEW; END; $$ LANGUAGE plpgsql");
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_insert BEFORE INSERT ON contactos_proveedores FOR EACH ROW EXECUTE FUNCTION fn_enforce_single_contacto_principal()");
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_update BEFORE UPDATE ON contactos_proveedores FOR EACH ROW EXECUTE FUNCTION fn_enforce_single_contacto_principal()");
            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_insert BEFORE INSERT ON contactos_proveedores FOR EACH ROW WHEN NEW.es_contacto_principal = 1 AND EXISTS (SELECT 1 FROM contactos_proveedores c WHERE c.proveedor_id = NEW.proveedor_id AND c.es_contacto_principal = 1) BEGIN SELECT RAISE(ABORT, 'Solo puede existir un contacto principal por proveedor'); END;");
            DB::unprepared("CREATE TRIGGER trg_contacto_principal_update BEFORE UPDATE ON contactos_proveedores FOR EACH ROW WHEN NEW.es_contacto_principal = 1 AND EXISTS (SELECT 1 FROM contactos_proveedores c WHERE c.proveedor_id = NEW.proveedor_id AND c.es_contacto_principal = 1 AND c.id <> NEW.id) BEGIN SELECT RAISE(ABORT, 'Solo puede existir un contacto principal por proveedor'); END;");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_update');
            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_insert ON contactos_proveedores');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_update ON contactos_proveedores');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_enforce_single_contacto_principal()');
        }
    }
};
