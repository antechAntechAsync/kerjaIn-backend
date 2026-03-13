<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InterestQuestion;
use App\Models\CareerRecommendation;
use App\Services\InterestAssessmentService;

class InterestAssessmentController extends Controller
{

    protected $assessmentService;

    public function __construct(InterestAssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    // GET Questions
    public function index()
    {

        $questions = InterestQuestion::with('options')->get();

        return response()->json([
            'status' => 'success',
            'questions' => $questions
        ]);
    }

    // POST Submit Answers
    public function submit(Request $request)
    {

        $studentId = Auth::id();

        $answers = $request->answers;

        if (!$answers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Answers are required'
            ], 400);
        }

        $this->assessmentService->processAssessment($studentId, $answers);

        return response()->json([
            'status' => 'success',
            'message' => 'Assessment submitted successfully'
        ]);
    }

    // GET Recommendation
    public function recommendations()
    {
        $studentId = Auth::id();

        $recommendations = CareerRecommendation::where('user_id', $studentId)
            ->latest()
            ->take(3)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $recommendations
        ]);
    }
}
