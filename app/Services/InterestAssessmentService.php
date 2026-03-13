<?php

namespace App\Services;

use App\Models\InterestResult;
use App\Models\CareerRecommendation;
use App\Models\CareerRole;
use App\Models\InterestOption;

class InterestAssessmentService
{

    protected $scoring;
    protected $ai;
    protected $roadmapBuilder;

    public function __construct(
        InterestScoringService $scoring,
        GroqAIService $ai,
        RoadmapBuilderService $roadmapBuilder
    ) {
        $this->scoring = $scoring;
        $this->ai = $ai;
        $this->roadmapBuilder = $roadmapBuilder;
    }

    public function processAssessment($studentId, $answers)
    {

        InterestResult::where('user_id', $studentId)->delete();
        CareerRecommendation::where('user_id', $studentId)->delete();

        $this->roadmapBuilder->clearUserRoadmaps($studentId);

        foreach ($answers as $questionId => $optionId) {

            $option = InterestOption::find($optionId);

            if (!$option) {
                continue;
            }

            InterestResult::create([
                'user_id' => $studentId,
                'question_id' => $questionId,
                'option_id' => $optionId,
                'score' => $option->score
            ]);
        }

        // hitung top subfields
        $subfields = $this->scoring->calculateTopSubfields($answers);

        if ($subfields->isEmpty()) {
            return;
        }

        // ambil hanya subfield tertinggi
        $topSubfield = $subfields->first();

        // ambil maksimal 3 career dari subfield tersebut
        $roles = CareerRole::where('subfield_id', $topSubfield->id)
            ->limit(3)
            ->get();

        $roleNames = $roles->pluck('name')->toArray();

        // generate roadmap
        $recommendations = $this->ai->generateCareerRecommendation($roleNames);

        foreach ($recommendations as $rec) {

            if (!isset($rec['role'])) {
                continue;
            }

            CareerRecommendation::create([
                'user_id' => $studentId,
                'career_role' => $rec['role'],
                'roadmap' => $rec['roadmap']
            ]);

            $this->roadmapBuilder->build(
                $studentId,
                $rec['role'],
                $rec['roadmap']
            );
        }
    }
}
