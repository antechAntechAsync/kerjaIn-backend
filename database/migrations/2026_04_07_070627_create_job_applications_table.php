<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignId('job_id')
                ->constrained('job_listings', 'id')
                ->cascadeOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects', 'id')
                ->nullOnDelete();

            $table->text('cover_letter')->nullable();

            $table->enum('status', [
                'pending',
                'reviewed',
                'accepted',
                'rejected'
            ])->default('pending');

            $table->timestamps();

            $table->unique(['user_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
