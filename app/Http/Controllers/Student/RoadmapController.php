<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserRoadmap;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoadmapController extends Controller
{
    /**
     * Get user's roadmap
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get active roadmap with nodes and progress
        $activeRoadmap = UserRoadmap::with([
            'roadmap.nodes' => function ($query) use ($user) {
                $query->with(['skill', 'resources'])
                    ->with(['progresses' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
            },
        ])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$activeRoadmap) {
            return response()->json([
                'message' => 'No active roadmap found',
                'roadmap' => null,
            ]);
        }

        // Calculate overall progress
        $totalNodes = $activeRoadmap->roadmap->nodes->count();
        $completedNodes = $activeRoadmap->roadmap->nodes
            ->filter(fn($node) => $node->progresses->first()?->is_completed ?? false)
            ->count();
        $progressPercentage = $totalNodes > 0 ? round(($completedNodes / $totalNodes) * 100, 2) : 0;

        return response()->json([
            'roadmap' => $activeRoadmap,
            'progress' => [
                'total_nodes' => $totalNodes,
                'completed_nodes' => $completedNodes,
                'percentage' => $progressPercentage,
            ],
        ]);
    }

    /**
     * Get roadmap details for a specific user
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        // Only allow users to view their own roadmap or if they are professionals
        if ($request->user()->id !== $userId && !$request->user()->isProfessional()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $activeRoadmap = UserRoadmap::with([
            'roadmap.nodes' => function ($query) use ($userId) {
                $query->with(['skill', 'resources'])
                    ->with(['progresses' => function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    }]);
            },
            'user',
        ])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$activeRoadmap) {
            return response()->json([
                'message' => 'No active roadmap found',
                'roadmap' => null,
            ]);
        }

        return response()->json([
            'roadmap' => $activeRoadmap,
        ]);
    }
}
