<?php

namespace App\Services;

use App\Models\UserProgress;
use Carbon\Carbon;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentScale;
use App\Models\AssessmentSession;
use App\Models\InterestSession;

class ProgressService
{

    private function calculateReadinessScore($sessionId)
    {
        $answers = AssessmentAnswer::where('session_id', $sessionId)->get();

        $totalScore = $answers->sum('scale_value');

        $skillCount = $answers->count();

        $maxScale = AssessmentScale::max('value');

        $maxScore = $skillCount * $maxScale;

        if ($maxScore == 0) {
            return 0;
        }

        return round(($totalScore / $maxScore) * 100);
    }

    public function getUserProgress($userId): object
    {

        $latestSession = InterestSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $answers = [];

        $readinessScore = 0;

        if ($latestSession) {

            $answers = AssessmentAnswer::where('session_id', $latestSession->id)
                ->pluck('scale_value', 'roadmap_node_id');

            $readinessScore = $this->calculateReadinessScore($latestSession->id);
        }

        $progress = UserProgress::with('node')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) use ($answers) {

                $nodeId = $item->roadmap_node_id;

                return [
                    'skill' => $item->node->skill_name,
                    // 'level' => $answers[$nodeId] ?? 0,
                    'completed' => $item->status === 'completed'
                ];
            });

        $total = $progress->count();

        $completed = $progress->where('completed', true)->count();

        $roadmapCompletion = $total > 0
            ? round(($completed / $total) * 100)
            : 0;

        return (object) [
            'roadmap_completion' => $roadmapCompletion,
            // 'readiness_score' => $readinessScore,
            'skills' => $progress
        ];
    }

    public function getSkillDetail($userId, $nodeId)
    {
        $result = new \stdClass();

        $progress = UserProgress::with('node.roadmap')
            ->where('user_id', $userId)
            ->where('roadmap_node_id', $nodeId)
            ->firstOrFail();

        $result->skill_name = $progress->node->skill_name;
        $result->description = $progress->node->description;
        $result->status = $progress->status;
        $result->completed_at = Carbon::parse($progress->completed_at)->format('Y-m-d');
        $result->completed = empty($progress->completed_at) ? false : true;

        return $result;
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

        return $this->getUserProgress($userId);
    }
}
