<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old table to restructure it
        Schema::dropIfExists('knowledge_check_attempts');

        Schema::create('knowledge_check_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('roadmap_node_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->boolean('is_passed')->default(false);
            $table->text('feedback')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('knowledge_check_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_check_questions', 'knowledge_check_attempt_id')) {
                $table->foreignId('knowledge_check_attempt_id')->nullable()->after('id')->constrained('knowledge_check_attempts')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_check_questions', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_check_questions', 'knowledge_check_attempt_id')) {
                $table->dropForeign(['knowledge_check_attempt_id']);
                $table->dropColumn('knowledge_check_attempt_id');
            }
        });

        Schema::dropIfExists('knowledge_check_attempts');
    }
};
