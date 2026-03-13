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

            $node = RoadmapNode::create([
                'roadmap_id' => $roadmap->id,
                'skill_name' => $skill['skill'],
                'order_index' => $index + 1
            ]);

            UserProgress::create([
                'user_id' => $userId,
                'roadmap_node_id' => $node->id
            ]);
        }
    }

    public function clearUserRoadmaps($userId)
    {
        $roadmapIds = UserRoadmap::where('user_id', $userId)
            ->pluck('roadmap_id');

        UserProgress::where('user_id', $userId)->delete();

        UserRoadmap::where('user_id', $userId)->delete();

        RoadmapNode::whereIn('roadmap_id', $roadmapIds)->delete();

        Roadmap::whereIn('id', $roadmapIds)->delete();
    }
}
