<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{

    protected $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function index()
    {
        $userId = Auth::id();

        $progress = $this->progressService->getUserProgress($userId);

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    public function show($nodeId)
    {

        $userId = Auth::id();

        $skill = $this->progressService->getSkillDetail($userId, $nodeId);

        return response()->json([
            'success' => true,
            'data' => $skill
        ]);
    }

    public function markComplete(Request $request)
    {

        $updatedProgress = $this->progressService->markCompleted(
            Auth::user()->id,
            $request->roadmap_node_id
        );

        $result = new \stdClass();
        $result->roadmap_completion = $updatedProgress->roadmap_completion;
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => "Progress updated successfully"
        ]);
    }
}
