<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RoadmapNode;
use App\Models\UserRoadmap;
use Illuminate\Http\Request;
use App\Services\SkillAssessmentService;
use Illuminate\Support\Facades\Auth;

class SkillAssessmentController extends Controller
{
    protected $service;

    public function __construct(SkillAssessmentService $service)
    {
        $this->service = $service;
    }

    public function questions()
    {
        $userId = Auth::id();

        $roadmapId = UserRoadmap::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->value('roadmap_id');

        $skills = RoadmapNode::where('roadmap_id', $roadmapId)
            ->orderBy('order_index')
            ->get();

        return response()->json([
            'questions' => $skills->map(function ($skill) {
                return [
                    'id' => $skill->id,
                    'question' => "Seberapa paham anda tentang skill {$skill->skill_name}?"
                ];
            })
        ]);
    }

    public function submit(Request $request)
    {

        $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.roadmap_node_id' => 'required|exists:roadmap_nodes,id',
            'answers.*.score' => 'required|integer|min:0|max:5',
            'answers.*.weight' => 'nullable|integer|min:1|max:5'
        ]);

        $result = $this->service->process(
            Auth::user()->id,
            $request->answers
        );

        return response()->json([
            'message' => 'Skill assessment completed',
            'data' => $result
        ]);
    }
}
