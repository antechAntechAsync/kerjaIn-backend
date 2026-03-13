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

    public function index(Request $request)
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

    public function update(Request $request)
    {

        $this->progressService->markCompleted(
            Auth::user()->id,
            $request->roadmap_node_id
        );

        return response()->json([
            'success' => true
        ]);
    }
}
