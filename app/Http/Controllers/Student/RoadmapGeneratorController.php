<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CareerRecommendation;
use App\Models\Roadmap;
use App\Models\UserRoadmap;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RoadmapGeneratorController extends Controller
{
    /**
     * Generate a new roadmap based on career recommendation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'career_recommendation_id' => 'required|exists:career_recommendations,id',
        ]);

        $user = $request->user();

        // Get the career recommendation
        $recommendation = CareerRecommendation::with('careerRole')
            ->findOrFail($request->career_recommendation_id);

        // Check if recommendation belongs to user
        if ($recommendation->student_id !== $user->id) {
            return response()->json([
                'message' => 'Career recommendation not found',
            ], 404);
        }

        // Find or create roadmap for this career role
        $roadmap = Roadmap::firstOrCreate(
            [
                'career_role' => $recommendation->career_role_id,
                'level' => 'beginner',
            ]
        );

        // Deactivate existing active roadmaps
        UserRoadmap::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create user roadmap
        $userRoadmap = UserRoadmap::create([
            'user_id' => $user->id,
            'roadmap_id' => $roadmap->id,
            'is_active' => true,
            'version' => 1,
        ]);

        // Initialize progress for all roadmap nodes
        $nodes = $roadmap->nodes;
        foreach ($nodes as $node) {
            UserProgress::firstOrCreate([
                'user_id' => $user->id,
                'roadmap_node_id' => $node->id,
            ], [
                'is_completed' => false,
                'progress_percentage' => 0,
            ]);
        }

        return response()->json([
            'message' => 'Roadmap generated successfully',
            'user_roadmap' => $userRoadmap->load('roadmap.nodes'),
        ], 201);
    }
}
