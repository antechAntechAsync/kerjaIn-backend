<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    use ApiResponse;

    /**
     * List own jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobListing::where('user_id', $request->user()->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $jobs = $query->withCount('applications')
            ->latest()
            ->paginate($request->input('per_page', 15));

        $data = $jobs->through(fn ($job) => [
            'id' => $job->id,
            'title' => $job->title,
            'employment_type' => $job->employment_type,
            'site_type' => $job->site_type,
            'industry' => $job->industry,
            'location' => $job->location,
            'status' => $job->status,
            'required_skills' => $job->required_skills ?? [],
            'total_applicants' => $job->applications_count,
            'created_at' => $job->created_at,
        ]);

        return $this->paginatedResponse($data, 'Jobs retrieved');
    }

    /**
     * Create a new job listing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'employment_type' => ['required', 'in:internship,part_time,full_time,contract,daily_worker'],
            'site_type' => ['required', 'in:wfo,wfh,hybrid'],
            'industry' => ['required', 'string', 'min:2', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'required_skills' => ['required', 'array', 'min:1'],
            'required_skills.*' => ['string', 'max:50'],
        ]);

        $job = JobListing::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        return $this->createdResponse([
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'employment_type' => $job->employment_type,
            'site_type' => $job->site_type,
            'industry' => $job->industry,
            'location' => $job->location,
            'status' => $job->status,
            'required_skills' => $job->required_skills,
            'total_applicants' => 0,
            'created_at' => $job->created_at,
        ], 'Job listing created successfully');
    }

    /**
     * Show job detail with applicant summary.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $job = JobListing::where('user_id', $request->user()->id)
            ->withCount('applications')
            ->findOrFail($id);

        $recentApplicants = $job->applications()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($app) => [
                'application_id' => $app->id,
                'applicant_name' => $app->user->name,
                'applicant_avatar' => $app->user->avatar,
                'match_score' => $app->match_score ?? 0,
                'applied_at' => $app->created_at,
            ]);

        return $this->successResponse([
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'employment_type' => $job->employment_type,
            'site_type' => $job->site_type,
            'industry' => $job->industry,
            'location' => $job->location,
            'status' => $job->status,
            'required_skills' => $job->required_skills ?? [],
            'total_applicants' => $job->applications_count,
            'recent_applicants' => $recentApplicants,
            'created_at' => $job->created_at,
        ], 'Job detail retrieved');
    }

    /**
     * Update a job listing.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $job = JobListing::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'description' => ['sometimes', 'string', 'min:20', 'max:5000'],
            'employment_type' => ['sometimes', 'in:internship,part_time,full_time,contract,daily_worker'],
            'site_type' => ['sometimes', 'in:wfo,wfh,hybrid'],
            'industry' => ['sometimes', 'string', 'min:2', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'required_skills' => ['sometimes', 'array', 'min:1'],
            'required_skills.*' => ['string', 'max:50'],
        ]);

        $job->update($validated);

        return $this->successResponse([
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'employment_type' => $job->employment_type,
            'site_type' => $job->site_type,
            'industry' => $job->industry,
            'location' => $job->location,
            'status' => $job->status,
            'required_skills' => $job->required_skills ?? [],
            'created_at' => $job->created_at,
        ], 'Job listing updated successfully');
    }

    /**
     * Toggle job status (open/closed).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $job = JobListing::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $newStatus = $job->status === 'open' ? 'closed' : 'open';
        $job->update(['status' => $newStatus]);

        return $this->successResponse([
            'id' => $job->id,
            'status' => $newStatus,
        ], 'Job status updated');
    }
}
