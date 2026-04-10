<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ReadinessScoreService;
use App\Services\SelfAssessmentService;
use Illuminate\Http\Request;

class SelfAssessmentController extends Controller
{
    protected $service;

    protected $readinessService;

    public function __construct(SelfAssessmentService $service, ReadinessScoreService $readinessService)
    {
        $this->service = $service;
        $this->readinessService = $readinessService;
    }

    public function questions(Request $request)
    {
        $user = $request->user();

        $data = $this->service->getQuestions($user->id);

        return response()->json($data);
    }

    public function submit(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'answers' => 'required|array',
        ]);

        $session = $this->service->submit(
            $user->id,
            $request->answers,
        );

        $readiness = $this->readinessService->calculateReadiness($session->id);

        $skills = $this->readinessService->getSkillReadiness($session->id);

        return response()->json([
            'message' => 'Assessment completed',
            'readiness_score' => $readiness,
            'skills' => $skills,
        ]);
    }
}
