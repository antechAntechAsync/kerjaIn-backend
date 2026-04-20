<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_skill_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_name')->index();
            $table->unsignedInteger('score')->default(0); // 0-100
            $table->enum('source', ['roadmap_completion', 'self_assessment'])->default('self_assessment');
            $table->foreignId('roadmap_node_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_scores');
    }
};
