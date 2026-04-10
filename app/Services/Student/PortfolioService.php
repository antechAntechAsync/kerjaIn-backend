<?php

namespace App\Services\Student;

use App\Models\Portfolio;

class PortfolioService
{
    public function create($userId, array $data)
    {
        return Portfolio::create([
            'user_id' => $userId,
            'title' => $data['title'] ?? null,
            'summary' => $data['summary'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);
    }

    public function getUserPortfolios($userId)
    {
        $portfolios = Portfolio::with([
            'user:id,name',
            'projects.skills.node',
            'projects.media',
        ])
            ->where('user_id', $userId)
            ->get();

        return $portfolios->map(function ($portfolio) {
            return [
                'id' => $portfolio->id,
                'user' => [
                    'id' => $portfolio->user->id,
                    'name' => $portfolio->user->name,
                ],
                'title' => $portfolio->title,
                'summary' => $portfolio->summary,

                'projects' => $portfolio->projects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'description' => $project->description,
                        'github_url' => $project->github_url,
                        'demo_url' => $project->demo_url,
                        'thumbnail_url' => $project->thumbnail_url,
                        'complexity_level' => $project->complexity_level,
                        'is_featured' => $project->is_featured,

                        'skills' => $project->skills->map(function ($skill) {
                            return [
                                'roadmap_node_id' => $skill->roadmap_node_id,
                                'name' => $skill->node->skill_name ?? null,
                            ];
                        }),

                        'media' => $project->media->map(function ($m) {
                            return [
                                'url' => $m->file_url,
                                'type' => $m->file_type,
                            ];
                        }),
                    ];
                }),
            ];
        });
    }
}
