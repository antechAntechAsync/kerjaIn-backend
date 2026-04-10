<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // normalize data lama
        DB::table('users')
            ->where('role', 'hr')
            ->update(['role' => 'professional']);

        // Ubah ENUM → STRING
        DB::statement('
            ALTER TABLE users
            MODIFY role VARCHAR(50) NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('student', 'professional') NOT NULL
        ");
    }
};
