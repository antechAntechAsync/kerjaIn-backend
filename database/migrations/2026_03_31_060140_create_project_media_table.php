<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects', 'id')
                ->cascadeOnDelete();

            $table->string('file_url');

            $table->enum('file_type', [
                'image',
                'video',
                'document',
            ])->default('image');

            $table->timestamps();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_media');
    }
};
