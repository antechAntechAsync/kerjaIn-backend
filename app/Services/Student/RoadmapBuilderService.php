<?php

namespace App\Services\Student;

use App\Models\Roadmap;
use App\Models\RoadmapNode;
use App\Models\Skill;
use App\Models\UserProgress;
use App\Models\UserRoadmap;

use App\Services\GroqAI\GroqAIService;

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

        $skillCache = [];

        foreach ($skills as $index => $skill) {

            if (!isset($skill['skill']) || empty(trim($skill['skill']))) {
                continue;
            }

            $skillName = strtolower(trim($skill['skill']));

            if (!isset($skillCache[$skillName])) {
                $skillCache[$skillName] = Skill::firstOrCreate([
                    'name' => $skillName
                ]);
            }

            $skillModel = $skillCache[$skillName];

            $node = RoadmapNode::create([
                'roadmap_id' => $roadmap->id,
                'skill_id' => $skillModel->id,
                'order_index' => $index + 1,
                'description' => $skill['description'] ?? null
            ]);

            UserProgress::create([
                'user_id' => $userId,
                'roadmap_node_id' => $node->id
            ]);

            // Menyimpan learning source
            foreach ($skill['resources'] ?? [] as $res) {

                if (
                    !isset($res['title']) ||
                    !isset($res['url']) ||
                    !filter_var($res['url'], FILTER_VALIDATE_URL)
                ) {
                    continue;
                }

                $node->resources()->create([
                    'title' => $res['title'],
                    'url' => $res['url'],
                    'type' => strtolower($res['type'] ?? 'unknown')
                ]);
            }
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

            $skillCache = [];

            foreach ($skills as $index => $skill) {

                if (!isset($skill['skill']) || empty(trim($skill['skill']))) {
                    continue;
                }

                $skillName = strtolower(trim($skill['skill']));

                if (!isset($skillCache[$skillName])) {
                    $skillCache[$skillName] = Skill::firstOrCreate([
                        'name' => $skillName
                    ]);
                }

                $skillModel = $skillCache[$skillName];

                $node = RoadmapNode::create([
                    'roadmap_id' => $roadmap->id,
                    'skill_id' => $skillModel->id,
                    'order_index' => $index + 1,
                    'description' => $skill['description'] ?? null
                ]);

                UserProgress::create([
                    'user_id' => $userId,
                    'roadmap_node_id' => $node->id
                ]);

                // Menyimpan learning source
                foreach ($skill['resources'] ?? [] as $res) {

                    if (
                        !isset($res['title']) ||
                        !isset($res['url']) ||
                        !filter_var($res['url'], FILTER_VALIDATE_URL)
                    ) {
                        continue;
                    }

                    $node->resources()->create([
                        'title' => $res['title'],
                        'url' => $res['url'],
                        'type' => strtolower($res['type'] ?? 'unknown')
                    ]);
                }
            }
        }
    }
}
