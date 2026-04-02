<?php

namespace App\Services;

use App\Models\Roadmap;
use App\Models\RoadmapNode;
use App\Models\UserProgress;
use App\Models\UserRoadmap;

class RoadmapBuilderService
{

    public function build($userId, $role, $skills)
    {

        $roadmap = Roadmap::create([
            'career_role' => $role
        ]);

        UserRoadmap::create([
            'user_id' => $userId,
            'roadmap_id' => $roadmap->id
        ]);

        foreach ($skills as $index => $skill) {

            if (!isset($skill['skill'])) continue;

            $node = RoadmapNode::create([
                'roadmap_id' => $roadmap->id,
                'skill_name' => $skill['skill'],
                'order_index' => $index + 1,
                'description' => $skill['description'] ?? null
            ]);

            UserProgress::create([
                'user_id' => $userId,
                'roadmap_node_id' => $node->id
            ]);
        }
    }

    // public function clearUserRoadmaps($userId)
    // {
    //     $roadmapIds = UserRoadmap::where('user_id', $userId)
    //         ->pluck('roadmap_id');

    //     UserProgress::where('user_id', $userId)->delete();

    //     UserRoadmap::where('user_id', $userId)->delete();

    //     RoadmapNode::whereIn('roadmap_id', $roadmapIds)->delete();

    //     Roadmap::whereIn('id', $roadmapIds)->delete();
    // }

    public function buildAdaptive($userId, $role, $level, $extraSkills)
{
    // 1. nonaktifkan roadmap lama
    UserRoadmap::where('user_id', $userId)
        ->where('is_active', true)
        ->update(['is_active' => false]);

    // 2. ambil roadmap AI
    $careers = app(GroqAIService::class)
        ->generateCareerRecommendation([$role]);

    foreach ($careers as $career) {

        $skills = $career['roadmap'];

        // 3. inject high demand skills
        foreach ($extraSkills as $skill) {
            $skills[] = [
                'skill' => $skill,
                'description' => 'High demand industry skill'
            ];
        }

        // 4. build roadmap baru
        $roadmap = Roadmap::create([
            'career_role' => $career['role'],
            'level' => $level
        ]);

        UserRoadmap::create([
            'user_id' => $userId,
            'roadmap_id' => $roadmap->id,
            'is_active' => true
        ]);

        foreach ($skills as $index => $skill) {
            $node = RoadmapNode::create([
                'roadmap_id' => $roadmap->id,
                'skill_name' => $skill['skill'],
                'order_index' => $index + 1,
                'description' => $skill['description'] ?? null
            ]);

            UserProgress::create([
                'user_id' => $userId,
                'roadmap_node_id' => $node->id
            ]);
        }
    }
}
}
