<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use stdClass;

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
            'data' => $progress,
        ]);
    }

    public function show($nodeId)
    {
        $userId = Auth::id();

        $skill = $this->progressService->getSkillDetail($userId, $nodeId);

        return response()->json([
            'success' => true,
            'data' => $skill,
        ]);
    }

    public function markComplete(Request $request)
    {
        $updatedProgress = $this->progressService->markCompleted(
            Auth::user()->id,
            $request->roadmap_node_id,
        );

        $result = new stdClass();
        $result->roadmap_completion = $updatedProgress->roadmap_completion;

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Progress updated successfully',
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'roadmap_node_id' => 'required|exists:roadmap_nodes,id',
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        $updatedProgress = $this->progressService->updateProgress(
            Auth::user()->id,
            $request->roadmap_node_id,
            $request->progress_percentage,
        );

        $result = new stdClass();
        $result->roadmap_completion = $updatedProgress->roadmap_completion;

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Progress updated successfully',
        ]);
    }
}
