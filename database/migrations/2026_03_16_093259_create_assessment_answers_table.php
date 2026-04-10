<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')
                ->constrained('assessment_sessions', 'id')
                ->cascadeOnDelete();

            $table->foreignId('roadmap_node_id')
                ->constrained('roadmap_nodes', 'id')
                ->cascadeOnDelete();

            $table->integer('scale_value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
