<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fallbackRoleId = DB::table('roles')
            ->orderBy('nivel_acceso')
            ->orderBy('id')
            ->value('id');

        $fallbackUserId = DB::table('users')
            ->orderBy('id')
            ->value('id');

        if ($fallbackRoleId) {
            DB::table('notificaciones_sistema')
                ->whereNull('user_id')
                ->whereNull('role_id')
                ->update(['role_id' => $fallbackRoleId]);
        } elseif ($fallbackUserId) {
            DB::table('notificaciones_sistema')
                ->whereNull('user_id')
                ->whereNull('role_id')
                ->update(['user_id' => $fallbackUserId]);
        } else {
            DB::table('notificaciones_sistema')
                ->whereNull('user_id')
                ->whereNull('role_id')
                ->delete();
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::unprepared("CREATE TRIGGER trg_notif_destinatario_insert BEFORE INSERT ON notificaciones_sistema FOR EACH ROW BEGIN IF NEW.user_id IS NULL AND NEW.role_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Notificacion requiere user_id o role_id'; END IF; END");
            DB::unprepared("CREATE TRIGGER trg_notif_destinatario_update BEFORE UPDATE ON notificaciones_sistema FOR EACH ROW BEGIN IF NEW.user_id IS NULL AND NEW.role_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Notificacion requiere user_id o role_id'; END IF; END");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notificaciones_sistema ADD CONSTRAINT chk_notif_destinatario CHECK (user_id IS NOT NULL OR role_id IS NOT NULL)');
            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER trg_notif_destinatario_insert BEFORE INSERT ON notificaciones_sistema FOR EACH ROW WHEN NEW.user_id IS NULL AND NEW.role_id IS NULL BEGIN SELECT RAISE(ABORT, 'Notificacion requiere user_id o role_id'); END;");
            DB::unprepared("CREATE TRIGGER trg_notif_destinatario_update BEFORE UPDATE ON notificaciones_sistema FOR EACH ROW WHEN NEW.user_id IS NULL AND NEW.role_id IS NULL BEGIN SELECT RAISE(ABORT, 'Notificacion requiere user_id o role_id'); END;");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_notif_destinatario_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_notif_destinatario_update');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notificaciones_sistema DROP CONSTRAINT IF EXISTS chk_notif_destinatario');
            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_notif_destinatario_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_notif_destinatario_update');
        }
    }
};
