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
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignId('roadmap_node_id')
                ->constrained('roadmap_nodes', 'id')
                ->cascadeOnDelete();

            $table->enum('status', [
                'not_started',
                'in_progress',
                'completed',
            ])->default('not_started');

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
