<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\KnowledgeCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeCheckController extends Controller
{
    protected $service;

    public function __construct(KnowledgeCheckService $service)
    {
        $this->service = $service;
    }

    public function questions()
    {
        return response()->json([
            'questions' => $this->service->getQuestions(Auth::id()),
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:knowledge_check_questions,id',
            'answers.*.selected_answer' => 'required|in:A,B,C,D',
        ]);

        // 🔒 Prevent duplicate question answers
        $questionIds = collect($request->answers)->pluck('question_id');

        if ($questionIds->count() !== $questionIds->unique()->count()) {
            return response()->json([
                'message' => 'Duplicate answers detected',
            ], 422);
        }

        $result = $this->service->submit(Auth::id(), $request->answers);

        return response()->json([
            'message' => 'Submitted',
            'data' => $result,
        ]);
    }
}
