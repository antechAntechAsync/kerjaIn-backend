<?php

namespace App\Services\Student;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentScale;

class ReadinessScoreService
{
    public function calculateReadiness($sessionId)
    {
        $answers = AssessmentAnswer::where('session_id', $sessionId)->get();

        $totalScore = $answers->sum('scale_value');

        $skillCount = $answers->count();

        $maxScale = AssessmentScale::max('value');

        $maxScore = $skillCount * $maxScale;

        $readiness = ($totalScore / $maxScore) * 100;

        return round($readiness, 2);
    }

    public function getSkillReadiness($sessionId)
    {
        $answers = AssessmentAnswer::with('roadmapNode')
            ->where('session_id', $sessionId)
            ->get();

        $maxScale = AssessmentScale::max('value');

        $skills = [];

        foreach ($answers as $answer) {
            $progress = ($answer->scale_value / $maxScale) * 100;

            $skills[] = [
                'skill' => $answer->roadmapNode->skill_name,
                'level' => $answer->scale_value,
                'progress' => round($progress, 2),
            ];
        }

        return $skills;
    }
}
