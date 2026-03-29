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
        Schema::create('knowledge_check_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('assessment_sessions', 'id')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('knowledge_check_questions', 'id')->cascadeOnDelete();
            $table->string('selected_answer');
            $table->boolean('is_correct');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_check_attempts');
    }
};
