<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->integer('total_projects')->default(0);
            $table->integer('total_skills_covered')->default(0);

            $table->decimal('avg_complexity_score', 5, 2)->default(0);
            $table->decimal('portfolio_score', 5, 2)->default(0);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_metrics');
    }
};
