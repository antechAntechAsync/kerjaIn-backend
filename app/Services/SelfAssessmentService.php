<?php

namespace App\Services;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentScale;
use App\Models\AssessmentSession;
use App\Models\RoadmapNode;
use App\Models\UserRoadmap;
use Exception;
use Illuminate\Support\Facades\DB;

class SelfAssessmentService
{
    public function getQuestions($userId)
    {
        $roadmapId = UserRoadmap::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->value('roadmap_id');

        if (!$roadmapId) {
            throw new Exception('Active roadmap not found');
        }

        $skills = RoadmapNode::where('roadmap_id', $roadmapId)
            ->orderBy('order_index')
            ->get();

        $scales = AssessmentScale::orderBy('value')->get();

        return [
            'skills' => $skills,
            'scales' => $scales,
        ];
    }

    public function submit($userId, $answers)
    {
        return DB::transaction(function () use ($userId, $answers) {
            $roadmapId = UserRoadmap::where('user_id', $userId)
                ->where('is_active', true)
                ->latest()
                ->value('roadmap_id');

            if (!$roadmapId) {
                throw new Exception('Active roadmap not found');
            }

            $session = AssessmentSession::create([
                'user_id' => $userId,
                'roadmap_id' => $roadmapId,
                'type' => 'self',
            ]);

            foreach ($answers as $answer) {
                AssessmentAnswer::create([
                    'session_id' => $session->id,
                    'roadmap_node_id' => $answer['roadmap_node_id'],
                    'scale_value' => $answer['scale'],
                ]);
            }

            return $session;
        });
    }
}
