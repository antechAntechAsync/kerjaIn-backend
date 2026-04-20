<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update existing student_profiles table (already created by 2026_03_07 migration).
     * Add missing columns: instagram_url, youtube_url, tiktok_url.
     * Also ensure school_name is NOT nullable (required per new spec).
     */
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'instagram_url')) {
                $table->string('instagram_url')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('student_profiles', 'youtube_url')) {
                $table->string('youtube_url')->nullable()->after('instagram_url');
            }
            if (!Schema::hasColumn('student_profiles', 'tiktok_url')) {
                $table->string('tiktok_url')->nullable()->after('youtube_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $columns = ['instagram_url', 'youtube_url', 'tiktok_url'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('student_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
