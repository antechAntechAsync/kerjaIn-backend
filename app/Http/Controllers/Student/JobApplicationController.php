<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\JobApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    protected $service;

    public function __construct(JobApplicationService $service)
    {
        $this->service = $service;
    }

    public function apply(Request $request, $id)
    {
        $validated = $request->validate([
            'cover_letter' => 'nullable|string',
        ]);

        $application = $this->service->apply(
            Auth::user()->id,
            $id,
            $validated['cover_letter'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'application_id' => $application->id,
            ],
            'message' => 'Application submitted',
        ], 201);
    }
}
