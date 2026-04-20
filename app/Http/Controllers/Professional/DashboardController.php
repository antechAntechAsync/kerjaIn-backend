<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Professional dashboard with stats.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->professionalProfile;

        // Job statistics
        $totalJobs = JobListing::where('user_id', $user->id)->count();
        $activeJobs = JobListing::where('user_id', $user->id)->where('status', 'open')->count();
        $closedJobs = $totalJobs - $activeJobs;

        // Applicant statistics
        $jobIds = JobListing::where('user_id', $user->id)->pluck('id');
        $totalApplicants = JobApplication::whereIn('job_listing_id', $jobIds)->count();
        $newApplicantsToday = JobApplication::whereIn('job_listing_id', $jobIds)
            ->whereDate('created_at', today())
            ->count();

        // Recent jobs
        $recentJobs = JobListing::where('user_id', $user->id)
            ->withCount('applications')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $job->status,
                'total_applicants' => $job->applications_count,
                'employment_type' => $job->employment_type,
                'site_type' => $job->site_type,
                'created_at' => $job->created_at,
            ]);

        // Recent applicants
        $recentApplicants = JobApplication::whereIn('job_listing_id', $jobIds)
            ->with(['user', 'jobListing'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($app) => [
                'application_id' => $app->id,
                'applicant_name' => $app->user->name,
                'applicant_avatar' => $app->user->avatar,
                'job_title' => $app->jobListing->title,
                'match_score' => $app->match_score ?? 0,
                'applied_at' => $app->created_at,
            ]);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'company_name' => $profile?->company_name,
                'industry' => $user->industry,
            ],
            'statistics' => [
                'total_jobs' => $totalJobs,
                'active_jobs' => $activeJobs,
                'closed_jobs' => $closedJobs,
                'total_applicants' => $totalApplicants,
                'new_applicants_today' => $newApplicantsToday,
            ],
            'recent_jobs' => $recentJobs,
            'recent_applicants' => $recentApplicants,
        ], 'Dashboard retrieved');
    }
}
