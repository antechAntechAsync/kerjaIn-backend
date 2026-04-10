<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Services\Professional\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    protected $jobService;

    public function __construct(JobService $jobService)
    {
        $this->jobService = $jobService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'level' => 'required|in:junior,mid,senior',
            'required_skills' => 'array',
        ]);

        $job = $this->jobService->createJob(
            $validated,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job->id
            ],
            'message' => 'Job created'
        ], 201);
    }
}
