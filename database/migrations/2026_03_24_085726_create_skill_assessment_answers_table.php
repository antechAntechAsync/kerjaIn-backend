<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_assessment_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_id')
                ->constrained('skill_assessments', 'id')
                ->cascadeOnDelete();

            $table->text('question');
            $table->text('answer')->nullable();

            $table->integer('score')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_assessment_answers');
    }
};
