<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service,
    ) {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'portfolio_id' => 'required|exists:portfolios,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'github_url' => 'nullable|url',
            'demo_url' => 'nullable|url',
            'skills' => 'array',
            'skills.*' => 'exists:roadmap_nodes,id',
        ]);

        $project = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'data' => ['project_id' => $project['id']],
            'message' => 'Project added',
        ], 201);
    }
}
