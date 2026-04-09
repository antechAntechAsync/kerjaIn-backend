<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portfolio_id')
                  ->constrained('portfolios', 'id')
                  ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('project_type', [
                'web',
                'mobile',
                'design',
                'documentation',
                'physical_work',
                'other'
            ])->default('other');

            $table->string('github_url')->nullable();
            $table->string('demo_url')->nullable();

            $table->string('thumbnail_url')->nullable();

            $table->enum('complexity_level', [
                'easy',
                'medium',
                'hard'
            ])->default('medium');

            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index('portfolio_id');
            $table->index('project_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
