<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\UserSkillScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Student dashboard — comprehensive overview.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Profile info
        $profile = $user->studentProfile;

        // Streak
        $streak = $user->dailyStreak;
        $streakData = [
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'checked_in_today' => $streak?->last_checkin_date?->isToday() ?? false,
        ];

        // Assessment status
        $latestInterestResult = $user->interestResults()
            ->with('interestField', 'interestSubfield')
            ->latest()
            ->first();

        $assessmentStatus = [
            'interest_completed' => $latestInterestResult !== null,
            'interest_field' => $latestInterestResult?->interestField?->name,
            'has_roadmap' => $user->activeRoadmap !== null,
        ];

        // Roadmap progress
        $roadmapProgress = null;
        $activeRoadmap = $user->activeRoadmap?->load('roadmap.nodes');
        if ($activeRoadmap && $activeRoadmap->roadmap) {
            $totalNodes = $activeRoadmap->roadmap->nodes->count();
            $completedNodes = $activeRoadmap->roadmap->nodes
                ->filter(fn ($n) => $n->userProgress($user->id)?->is_completed ?? false)
                ->count();

            $roadmapProgress = [
                'career_role' => $activeRoadmap->roadmap->career_role ?? $activeRoadmap->roadmap->careerRole?->name,
                'total_nodes' => $totalNodes,
                'completed_nodes' => $completedNodes,
                'percentage' => $totalNodes > 0 ? round(($completedNodes / $totalNodes) * 100) : 0,
            ];
        }

        // Skill scores
        $skillScores = UserSkillScore::where('user_id', $user->id)
            ->orderByDesc('score')
            ->limit(10)
            ->get(['skill_name', 'score']);

        // Portfolio & applications
        $portfolioCount = $user->portfolios()->count();
        $applicationStats = [
            'total' => $user->jobApplications()->count(),
            'pending' => $user->jobApplications()->where('status', 'pending')->count(),
        ];

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'school_name' => $profile?->school_name,
                'industry' => $user->industry,
            ],
            'streak' => $streakData,
            'assessment_status' => $assessmentStatus,
            'roadmap_progress' => $roadmapProgress,
            'skill_scores' => $skillScores,
            'portfolio_count' => $portfolioCount,
            'applications' => $applicationStats,
        ], 'Dashboard retrieved');
    }
}
