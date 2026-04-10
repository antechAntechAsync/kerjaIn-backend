<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();

            // Relasi ke professional (user)
            $table->foreignId('professional_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('location');

            $table->enum('level', ['junior', 'mid', 'senior']);

            $table->timestamps();

            // Index untuk filtering
            $table->index('location');
            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
