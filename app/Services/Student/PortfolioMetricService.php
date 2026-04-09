<?php

namespace App\Services;

use App\Models\PortfolioMetric;
use App\Models\User;

class PortfolioMetricService
{
    public function recalculate($userId)
    {
        $user = User::with([
            'portfolios.projects.skills'
        ])->find($userId);

        if (!$user) return null;

        $projects = $user->portfolios->flatMap->projects;

        $totalProjects = $projects->count();

        $totalSkills = $projects
            ->flatMap->skills
            ->unique('roadmap_node_id')
            ->count();

        $avgComplexity = $projects->avg(fn ($p) => $p->complexity_score) ?? 0;

        $portfolioScore = (
            ($totalProjects * 0.4) +
            ($totalSkills * 0.3) +
            ($avgComplexity * 0.3)
        );

        return PortfolioMetric::updateOrCreate(
            ['user_id' => $userId],
            [
                'total_projects' => $totalProjects,
                'total_skills_covered' => $totalSkills,
                'avg_complexity_score' => $avgComplexity,
                'portfolio_score' => $portfolioScore,
            ]
        );
    }
}
