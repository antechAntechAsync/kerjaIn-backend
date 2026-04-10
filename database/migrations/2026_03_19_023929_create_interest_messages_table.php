<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interest_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('interest_sessions', 'id')->cascadeOnDelete();
            $table->enum('sender', ['user', 'ai']);
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_messages');
    }
};
