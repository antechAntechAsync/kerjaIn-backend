<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\UserSkillScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    use ApiResponse;

    /**
     * List all open jobs with filters and match score.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = JobListing::where('status', 'open');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by industry
        if ($industry = $request->input('industry')) {
            $query->where('industry', $industry);
        }

        // Filter by employment type (comma-separated)
        if ($types = $request->input('type')) {
            $query->whereIn('employment_type', explode(',', $types));
        }

        // Filter by site type (comma-separated)
        if ($sites = $request->input('site')) {
            $query->whereIn('site_type', explode(',', $sites));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'applicants_most' => $query->withCount('applications')->orderByDesc('applications_count'),
            'applicants_least' => $query->withCount('applications')->orderBy('applications_count'),
            default => $query->latest(), // newest
        };

        $perPage = min($request->input('per_page', 15), 50);
        $jobs = $query->withCount('applications')->paginate($perPage);

        // Get user skill scores for match calculation
        $userSkills = UserSkillScore::where('user_id', $user->id)
            ->pluck('score', 'skill_name')
            ->mapWithKeys(fn ($score, $name) => [strtolower(trim($name)) => $score])
            ->toArray();

        // Applied job IDs
        $appliedJobIds = JobApplication::where('user_id', $user->id)
            ->pluck('job_listing_id')
            ->toArray();

        // Transform response
        $data = $jobs->through(function ($job) use ($userSkills, $appliedJobIds) {
            $matchScore = $this->calculateMatchScore($userSkills, $job->required_skills ?? []);

            return [
                'id' => $job->id,
                'title' => $job->title,
                'description' => \Illuminate\Support\Str::limit($job->description, 200),
                'company_name' => $job->user?->professionalProfile?->company_name ?? $job->user?->name,
                'company_avatar' => $job->user?->avatar,
                'employment_type' => $job->employment_type,
                'site_type' => $job->site_type,
                'industry' => $job->industry,
                'location' => $job->location,
                'required_skills' => $job->required_skills ?? [],
                'match_score' => $matchScore,
                'total_applicants' => $job->applications_count,
                'is_applied' => in_array($job->id, $appliedJobIds),
                'created_at' => $job->created_at,
            ];
        });

        // Sort by match_score if requested (post-query)
        if ($sort === 'match_score') {
            $sorted = collect($data->items())->sortByDesc('match_score')->values()->all();
            $data = new \Illuminate\Pagination\LengthAwarePaginator(
                $sorted,
                $jobs->total(),
                $jobs->perPage(),
                $jobs->currentPage(),
            );
        }

        return $this->paginatedResponse($data, 'Job listings retrieved');
    }

    /**
     * Job detail with match score breakdown.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = JobListing::with('user.professionalProfile')
            ->withCount('applications')
            ->findOrFail($id);

        $userSkills = UserSkillScore::where('user_id', $user->id)
            ->pluck('score', 'skill_name')
            ->mapWithKeys(fn ($score, $name) => [strtolower(trim($name)) => $score])
            ->toArray();

        $requiredSkills = $job->required_skills ?? [];
        $matchScore = $this->calculateMatchScore($userSkills, $requiredSkills);

        // Match details
        $matchedSkills = [];
        $unmatchedSkills = [];
        foreach ($requiredSkills as $skill) {
            $normalizedSkill = strtolower(trim($skill));
            if (isset($userSkills[$normalizedSkill])) {
                $matchedSkills[] = [
                    'skill' => $skill,
                    'score' => $userSkills[$normalizedSkill],
                ];
            } else {
                $unmatchedSkills[] = $skill;
            }
        }

        $isApplied = JobApplication::where('user_id', $user->id)
            ->where('job_listing_id', $job->id)
            ->exists();

        return $this->successResponse([
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'company' => [
                'name' => $job->user?->professionalProfile?->company_name ?? $job->user?->name,
                'avatar' => $job->user?->avatar,
                'industry' => $job->user?->industry,
            ],
            'employment_type' => $job->employment_type,
            'site_type' => $job->site_type,
            'industry' => $job->industry,
            'location' => $job->location,
            'status' => $job->status,
            'required_skills' => $requiredSkills,
            'match_score' => $matchScore,
            'match_details' => [
                'matched_skills' => $matchedSkills,
                'unmatched_skills' => $unmatchedSkills,
            ],
            'total_applicants' => $job->applications_count,
            'is_applied' => $isApplied,
            'created_at' => $job->created_at,
        ], 'Job detail retrieved');
    }

    /**
     * Apply to a job.
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $job = JobListing::findOrFail($id);

        if ($job->status !== 'open') {
            return $this->errorResponse('This job is no longer accepting applications', 403);
        }

        $existing = JobApplication::where('user_id', $user->id)
            ->where('job_listing_id', $job->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('You have already applied to this job', 400);
        }

        // Calculate match score
        $userSkills = UserSkillScore::where('user_id', $user->id)
            ->pluck('score', 'skill_name')
            ->mapWithKeys(fn ($score, $name) => [strtolower(trim($name)) => $score])
            ->toArray();

        $matchScore = $this->calculateMatchScore($userSkills, $job->required_skills ?? []);

        $application = JobApplication::create([
            'user_id' => $user->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => $matchScore,
        ]);

        return $this->createdResponse([
            'application_id' => $application->id,
            'job_id' => $job->id,
            'job_title' => $job->title,
            'status' => 'pending',
            'applied_at' => $application->created_at,
        ], 'Application submitted successfully');
    }

    /**
     * List applied jobs.
     */
    public function applied(Request $request): JsonResponse
    {
        $applications = JobApplication::where('user_id', $request->user()->id)
            ->with(['jobListing.user.professionalProfile'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        $data = $applications->through(function ($app) {
            $job = $app->jobListing;

            return [
                'application_id' => $app->id,
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company_name' => $job->user?->professionalProfile?->company_name ?? $job->user?->name,
                    'company_avatar' => $job->user?->avatar,
                    'employment_type' => $job->employment_type,
                    'site_type' => $job->site_type,
                    'status' => $job->status,
                ],
                'match_score' => $app->match_score,
                'applied_at' => $app->created_at,
            ];
        });

        return $this->paginatedResponse($data, 'Applied jobs retrieved');
    }

    /**
     * Calculate match score between user skills and required skills.
     */
    protected function calculateMatchScore(array $userSkills, array $requiredSkills): int
    {
        if (empty($requiredSkills)) {
            return 0;
        }

        $totalScore = 0;
        foreach ($requiredSkills as $skill) {
            $normalized = strtolower(trim($skill));
            if (isset($userSkills[$normalized])) {
                $totalScore += $userSkills[$normalized];
            }
        }

        return (int) round($totalScore / count($requiredSkills));
    }
}
