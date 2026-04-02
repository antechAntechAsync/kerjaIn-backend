<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roadmap_nodes', function (Blueprint $table) {
            $table->text('description')->nullable()->after('skill_name');
        });
    }

    public function down(): void
    {
        Schema::table('roadmap_nodes', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
