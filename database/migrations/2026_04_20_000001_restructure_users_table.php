<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restructure users table for KerjaIn v1.
     *
     * Changes:
     * - Add: industry, linkedin_url, is_profile_completed
     * - Modify: role enum to include 'admin', 'professional'
     * - Remove: user_id, role_name, position, department, line_manager, seconde_line_manager, join_date, last_login
     * - Add: soft deletes for admin user management
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new columns
            $table->string('industry')->nullable()->after('phone_number');
            $table->string('linkedin_url')->nullable()->after('industry');
            $table->boolean('is_profile_completed')->default(false)->after('provider');
            $table->softDeletes();
        });

        // Drop deprecated columns (if they exist)
        Schema::table('users', function (Blueprint $table) {
            $columns = ['role_name', 'position', 'department', 'line_manager', 'seconde_line_manager', 'join_date', 'last_login'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Drop user_id separately (may have index)
        if (Schema::hasColumn('users', 'user_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['industry', 'linkedin_url', 'is_profile_completed']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id')->nullable();
            $table->string('role_name')->nullable();
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('line_manager')->nullable();
            $table->string('seconde_line_manager')->nullable();
            $table->string('join_date')->nullable();
            $table->string('last_login')->nullable();
        });
    }
};
