<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update job_listings for new spec:
     * - Add employment_type enum
     * - Add site_type enum
     * - Add industry field
     * - Add location
     * - Modify status to enum
     * - Change required_skills to JSON
     */
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('job_listings', 'employment_type')) {
                $table->string('employment_type')->default('full_time')->after('description');
            }
            if (!Schema::hasColumn('job_listings', 'site_type')) {
                $table->string('site_type')->default('wfo')->after('employment_type');
            }
            if (!Schema::hasColumn('job_listings', 'industry')) {
                $table->string('industry')->nullable()->after('site_type');
            }
            if (!Schema::hasColumn('job_listings', 'location')) {
                $table->string('location')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('job_listings', 'required_skills')) {
                $table->json('required_skills')->nullable()->after('location');
            }
            if (!Schema::hasColumn('job_listings', 'status')) {
                $table->string('status')->default('open')->after('required_skills');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $columns = ['employment_type', 'site_type', 'industry', 'location', 'required_skills'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('job_listings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
