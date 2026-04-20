<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // normalize data lama
        DB::table('users')
            ->where('role', 'hr')
            ->update(['role' => 'professional']);

        // Ubah ENUM → STRING - database agnostic approach
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE users
                MODIFY role VARCHAR(50) NOT NULL
            ');
        } elseif (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY, need to recreate table
            Schema::table('users', function ($table) {
                $table->string('role', 50)->change();
            });
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE users
                ALTER COLUMN role TYPE VARCHAR(50)
            ');
        }
    }

    public function down(): void
    {
        // Revert back to ENUM - database agnostic approach
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY role ENUM('student', 'professional') NOT NULL
            ");
        } elseif (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ENUM, use CHECK constraint instead
            Schema::table('users', function ($table) {
                $table->string('role', 20)->change();
            });
            DB::statement("
                CREATE TRIGGER validate_user_role
                BEFORE INSERT ON users
                FOR EACH ROW
                WHEN NEW.role NOT IN ('student', 'professional')
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid role value');
                END
            ");
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE users
                ALTER COLUMN role TYPE VARCHAR(20)
            ");
            DB::statement("
                ALTER TABLE users
                ADD CONSTRAINT check_user_role CHECK (role IN ('student', 'professional'))
            ");
        }
    }
};
