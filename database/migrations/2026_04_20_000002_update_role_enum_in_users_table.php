<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update role enum to include 'professional' and 'admin'.
     *
     * Note: MySQL doesn't support ALTER ENUM easily, so we use
     * a column modification approach.
     */
    public function up(): void
    {
        // For MySQL: modify the enum to support new roles
        // For SQLite: this is handled differently
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't enforce enums, so no migration needed
            return;
        }

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE users MODIFY COLUMN role ENUM('student', 'hr', 'professional', 'admin') DEFAULT 'student'"
        );
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE users MODIFY COLUMN role ENUM('hr', 'student') DEFAULT 'student'"
        );
    }
};
