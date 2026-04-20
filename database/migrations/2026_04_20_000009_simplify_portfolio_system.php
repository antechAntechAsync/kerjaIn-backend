<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simplify portfolio system:
     * - Add is_public, external_link to portfolios
     * - Create portfolio_images (replaces project_media)
     * - Create portfolio_skills (replaces project_skills)
     */
    public function up(): void
    {
        // Update portfolios table
        Schema::table('portfolios', function (Blueprint $table) {
            if (!Schema::hasColumn('portfolios', 'external_link')) {
                $table->string('external_link')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('portfolios', 'description')) {
                $table->text('description')->nullable()->after('external_link');
            }
            if (!Schema::hasColumn('portfolios', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('description');
            }
        });

        // Create portfolio_images
        Schema::create('portfolio_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedTinyInteger('order')->default(1); // 1 or 2, max 2
            $table->timestamps();
        });

        // Create portfolio_skills
        Schema::create('portfolio_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_skills');
        Schema::dropIfExists('portfolio_images');

        Schema::table('portfolios', function (Blueprint $table) {
            $columns = ['external_link', 'description', 'is_public'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('portfolios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
