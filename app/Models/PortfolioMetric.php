<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PortfolioMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_projects',
        'total_skills_covered',
        'avg_complexity_score',
        'portfolio_score',
    ];

    protected $casts = [
        'total_projects' => 'integer',
        'total_skills_covered' => 'integer',
        'avg_complexity_score' => 'float',
        'portfolio_score' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function recalculate($userId)
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

        return self::updateOrCreate(
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
