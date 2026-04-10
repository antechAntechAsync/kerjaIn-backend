<?php

namespace App\Services;

use App\Models\SkillAssessment;
use App\Models\SkillAssessmentAnswer;
use App\Models\InterestSession;
use Illuminate\Support\Facades\DB;

use App\Services\GroqAI\GroqAIService;
use App\Services\Student\RoadmapBuilderService;

class SkillAssessmentService
{
    protected $ai;
    protected $roadmapBuilder;

    public function __construct(
        GroqAIService $ai,
        RoadmapBuilderService $roadmapBuilder
    ) {
        $this->ai = $ai;
        $this->roadmapBuilder = $roadmapBuilder;
    }

    public function process($userId, array $answers)
    {
        return DB::transaction(function () use ($userId, $answers) {

            // 1. Hitung score & level
            $scoring = $this->calculateScore($answers);

            // 2. Ambil role dari interest
            $role = InterestSession::where('user_id', $userId)
                ->where('status', 'completed')
                ->latest()
                ->value('result_role');

            if (!$role) {
                throw new \Exception("Role not found");
            }

            // 3. Generate high demand skills
            $highDemandSkills = $this->ai
                ->generateHighDemandSkills($role, $scoring['level']);

            // 4. Simpan assessment
            $assessment = SkillAssessment::create([
                'user_id' => $userId,
                'role' => $role,
                'level' => $scoring['level'],
                'score' => $scoring['percentage']
            ]);

            // 5. Simpan answers
            foreach ($answers as $ans) {
                SkillAssessmentAnswer::create([
                    'assessment_id' => $assessment->id,
                    // 'question' => $ans['question'],
                    // 'answer' => $ans['answer'] ?? null,
                    // 'score' => $ans['score']
                    'roadmap_node_id' => $ans['roadmap_node_id'],
                    'score' => $ans['score']
                ]);
            }

            // 6. Build adaptive roadmap (NO DELETE)
            $this->roadmapBuilder->buildAdaptive(
                $userId,
                $role,
                $scoring['level'],
                $highDemandSkills
            );

            return [
                'level' => $scoring['level'],
                'score' => $scoring['percentage'],
                'high_demand_skills' => $highDemandSkills
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SCORING ENGINE
    |--------------------------------------------------------------------------
    */

    private function calculateScore(array $answers)
    {
        $totalScore = 0;
        $maxScore = 0;

        foreach ($answers as $ans) {

            $score = $ans['score'] ?? 0;
            $weight = $ans['weight'] ?? 1;

            $totalScore += ($score * $weight);
            $maxScore += (5 * $weight); // asumsi max per question = 5
        }

        $percentage = $maxScore > 0
            ? ($totalScore / $maxScore) * 100
            : 0;

        return [
            'percentage' => round($percentage),
            'level' => $this->mapLevel($percentage)
        ];
    }

    private function mapLevel($percentage)
    {
        return match (true) {
            $percentage <= 20 => 'beginner',
            $percentage <= 40 => 'basic',
            $percentage <= 60 => 'intermediate',
            $percentage <= 80 => 'advanced',
            default => 'expert',
        };
    }
}
