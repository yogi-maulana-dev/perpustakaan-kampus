<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Database trigger (PostgreSQL) untuk pencatatan otomatis:
 *  - users AFTER UPDATE  → catat perubahan STATUS akun ke activity_logs.
 *  - login_attempts AFTER INSERT → tandai IP mencurigakan (>5 percobaan/hari) ke activity_logs.
 *
 * Di-skip pada driver non-pgsql (mis. sqlite saat menjalankan test) agar migrasi tetap jalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1) Log perubahan status akun.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION trg_log_user_status_change() RETURNS trigger AS $$
            BEGIN
                IF NEW.status IS DISTINCT FROM OLD.status THEN
                    INSERT INTO activity_logs (user_id, user_name, action, description, created_at, updated_at)
                    VALUES (NEW.id, NEW.name, 'user_status_changed',
                            'Status akun diubah: ' || COALESCE(OLD.status, '-') || ' -> ' || COALESCE(NEW.status, '-'),
                            now(), now());
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS users_status_change_trigger ON users;
            CREATE TRIGGER users_status_change_trigger
            AFTER UPDATE ON users
            FOR EACH ROW EXECUTE FUNCTION trg_log_user_status_change();
        SQL);

        // 2) Tandai IP mencurigakan saat login_attempts bertambah.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION trg_flag_suspicious_login() RETURNS trigger AS $$
            DECLARE
                cnt integer;
            BEGIN
                SELECT count(*) INTO cnt FROM login_attempts
                  WHERE ip_address = NEW.ip_address
                    AND created_at >= date_trunc('day', now());

                IF cnt = 6 THEN
                    INSERT INTO activity_logs (user_name, action, description, ip_address, created_at, updated_at)
                    VALUES ('SYSTEM', 'suspicious_ip_detected',
                            'IP ' || COALESCE(NEW.ip_address, '?') || ' melewati 5 percobaan login dalam sehari.',
                            NEW.ip_address, now(), now());
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS login_attempts_suspicious_trigger ON login_attempts;
            CREATE TRIGGER login_attempts_suspicious_trigger
            AFTER INSERT ON login_attempts
            FOR EACH ROW EXECUTE FUNCTION trg_flag_suspicious_login();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS users_status_change_trigger ON users;');
        DB::unprepared('DROP FUNCTION IF EXISTS trg_log_user_status_change();');
        DB::unprepared('DROP TRIGGER IF EXISTS login_attempts_suspicious_trigger ON login_attempts;');
        DB::unprepared('DROP FUNCTION IF EXISTS trg_flag_suspicious_login();');
    }
};
