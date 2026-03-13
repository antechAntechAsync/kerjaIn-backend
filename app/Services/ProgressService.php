<?php

namespace App\Services;

use App\Models\UserProgress;

class ProgressService
{

    public function getUserProgress($userId)
    {

        $progress = UserProgress::with('node.roadmap')
            ->where('user_id', $userId)
            ->get();

        $total = $progress->count();

        $completed = $progress->where('status', 'completed')->count();

        $percentage = $total > 0
            ? round(($completed / $total) * 100)
            : 0;

        return [
            'completion_percentage' => $percentage,
            'total_skills' => $total,
            'completed_skills' => $completed,
            'skills' => $progress
        ];
    }

    public function getSkillDetail($userId, $nodeId)
    {

        return UserProgress::with('node.roadmap')
            ->where('user_id', $userId)
            ->where('roadmap_node_id', $nodeId)
            ->firstOrFail();
    }

    public function markCompleted($userId, $nodeId)
    {

        $progress = UserProgress::where('user_id', $userId)
            ->where('roadmap_node_id', $nodeId)
            ->firstOrFail();

        $progress->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }
}
