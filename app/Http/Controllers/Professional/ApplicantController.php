<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\UserSkillScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    use ApiResponse;

    /**
     * List all applicants across all jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = JobApplication::whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id))
            ->with(['user', 'jobListing']);

        if ($search = $request->input('search')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($jobId = $request->input('job_id')) {
            $query->where('job_listing_id', $jobId);
        }

        $applications = $query->latest()
            ->paginate($request->input('per_page', 15));

        $data = $applications->through(fn ($app) => [
            'application_id' => $app->id,
            'job' => [
                'id' => $app->jobListing->id,
                'title' => $app->jobListing->title,
            ],
            'applicant' => [
                'id' => $app->user->id,
                'name' => $app->user->name,
                'avatar' => $app->user->avatar,
                'bio' => $app->user->studentProfile?->bio,
                'match_score' => $app->match_score ?? 0,
                'email' => $app->user->email,
                'phone_number' => $app->user->phone_number,
            ],
            'applied_at' => $app->created_at,
        ]);

        return $this->paginatedResponse($data, 'Applicants retrieved');
    }

    /**
     * List applicants for a specific job.
     */
    public function byJob(Request $request, int $id): JsonResponse
    {
        $job = JobListing::where('user_id', $request->user()->id)
            ->withCount('applications')
            ->findOrFail($id);

        $sort = $request->input('sort', 'newest');
        $query = $job->applications()->with(['user.studentProfile']);

        if ($sort === 'match_score') {
            $query->orderByDesc('match_score');
        } else {
            $query->latest();
        }

        $applications = $query->paginate($request->input('per_page', 15));

        $data = $applications->through(fn ($app) => [
            'application_id' => $app->id,
            'name' => $app->user->name,
            'avatar' => $app->user->avatar,
            'bio' => $app->user->studentProfile?->bio,
            'match_score' => $app->match_score ?? 0,
            'email' => $app->user->email,
            'phone_number' => $app->user->phone_number,
            'applied_at' => $app->created_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job applicants retrieved',
            'data' => [
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'total_applicants' => $job->applications_count,
                ],
                'applicants' => $data->items(),
            ],
            'meta' => [
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'last_page' => $applications->lastPage(),
            ],
        ]);
    }

    /**
     * Get applicant detail for a specific application.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $application = JobApplication::whereHas('jobListing', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['user.studentProfile', 'jobListing'])
            ->findOrFail($id);

        $user = $application->user;
        $job = $application->jobListing;

        // Match details
        $userSkills = UserSkillScore::where('user_id', $user->id)
            ->pluck('score', 'skill_name')
            ->mapWithKeys(fn ($score, $name) => [strtolower(trim($name)) => $score])
            ->toArray();

        $requiredSkills = $job->required_skills ?? [];
        $matchedSkills = [];
        $unmatchedSkills = [];

        foreach ($requiredSkills as $skill) {
            $normalized = strtolower(trim($skill));
            if (isset($userSkills[$normalized])) {
                $matchedSkills[] = ['skill' => $skill, 'score' => $userSkills[$normalized]];
            } else {
                $unmatchedSkills[] = $skill;
            }
        }

        return $this->successResponse([
            'application_id' => $application->id,
            'applied_at' => $application->created_at,
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'required_skills' => $requiredSkills,
            ],
            'applicant' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'bio' => $user->studentProfile?->bio,
                'school_name' => $user->studentProfile?->school_name,
                'industry' => $user->industry,
                'linkedin_url' => $user->linkedin_url,
                'match_score' => $application->match_score ?? 0,
            ],
            'match_details' => [
                'matched_skills' => $matchedSkills,
                'unmatched_skills' => $unmatchedSkills,
            ],
        ], 'Applicant detail retrieved');
    }

    /**
     * Full applicant profile (including portfolio).
     */
    public function profile(Request $request, int $id): JsonResponse
    {
        $application = JobApplication::whereHas('jobListing', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['user.studentProfile', 'user.portfolios.images', 'user.portfolios.skills', 'user.dailyStreak', 'user.activeRoadmap.roadmap'])
            ->findOrFail($id);

        $user = $application->user;
        $profile = $user->studentProfile;

        // Skills
        $skills = UserSkillScore::where('user_id', $user->id)
            ->orderByDesc('score')
            ->get(['skill_name', 'score']);

        // Roadmap progress
        $roadmapProgress = null;
        $activeRoadmap = $user->activeRoadmap;
        if ($activeRoadmap && $activeRoadmap->roadmap) {
            $totalNodes = $activeRoadmap->roadmap->nodes()->count();
            $completedNodes = $activeRoadmap->roadmap->nodes()
                ->whereHas('progress', fn ($q) => $q->where('user_id', $user->id)->where('is_completed', true))
                ->count();

            $roadmapProgress = [
                'career_role' => $activeRoadmap->roadmap->career_role,
                'completion' => $totalNodes > 0 ? round(($completedNodes / $totalNodes) * 100) : 0,
                'completed_nodes' => $completedNodes,
                'total_nodes' => $totalNodes,
            ];
        }

        // Public portfolios only
        $portfolios = $user->portfolios
            ->where('is_public', true)
            ->map(fn ($p) => [
                'id' => $p->id,
                'description' => $p->description,
                'external_link' => $p->external_link,
                'skills' => $p->skills->pluck('skill_name')->toArray(),
                'images' => $p->images->map(fn ($i) => \Illuminate\Support\Facades\Storage::disk('public')->url($i->image_path))->toArray(),
                'created_at' => $p->created_at,
            ])->values();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'industry' => $user->industry,
                'linkedin_url' => $user->linkedin_url,
                'instagram_url' => $profile?->instagram_url,
                'school_name' => $profile?->school_name,
                'bio' => $profile?->bio,
            ],
            'match_score' => $application->match_score ?? 0,
            'skills' => $skills,
            'roadmap_progress' => $roadmapProgress,
            'portfolios' => $portfolios,
            'streak' => [
                'current_streak' => $user->dailyStreak?->current_streak ?? 0,
                'longest_streak' => $user->dailyStreak?->longest_streak ?? 0,
            ],
        ], 'Applicant profile retrieved');
    }
}
