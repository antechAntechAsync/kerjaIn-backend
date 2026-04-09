<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roadmap_nodes', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('roadmap_id');
        });

        $nodes = DB::table('roadmap_nodes')->get();

        foreach ($nodes as $node) {

            if (!$node->skill_name) continue;

            $skill = DB::table('skills')->where('name', strtolower($node->skill_name))->first();

            if (!$skill) {
                $skillId = DB::table('skills')->insertGetId([
                    'name' => strtolower($node->skill_name),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $skillId = $skill->id;
            }

            DB::table('roadmap_nodes')
                ->where('id', $node->id)
                ->update(['skill_id' => $skillId]);
        }

        Schema::table('roadmap_nodes', function (Blueprint $table) {
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roadmap_nodes', function (Blueprint $table) {
            //
        });
    }
};
