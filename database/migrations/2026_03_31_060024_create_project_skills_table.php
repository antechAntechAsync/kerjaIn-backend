<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                  ->constrained('projects', 'id')
                  ->cascadeOnDelete();

            $table->foreignId('roadmap_node_id')
                  ->constrained('roadmap_nodes', 'id')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['project_id', 'roadmap_node_id']);
            $table->index('roadmap_node_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_skills');
    }
};
