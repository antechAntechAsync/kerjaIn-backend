<?php

namespace App\Services\Student;

use App\Models\Project;
use App\Models\ProjectMedia;
use App\Models\ProjectSkill;
use App\Models\RoadmapNode;
use App\Models\UserSkillStat;
use App\Services\PortfolioMetricService;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create project
            $project = Project::create([
                'portfolio_id' => $data['portfolio_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_type' => $data['project_type'] ?? 'other',
                'github_url' => $data['github_url'] ?? null,
                'demo_url' => $data['demo_url'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'complexity_level' => $data['complexity_level'] ?? 'medium',
                'is_featured' => $data['is_featured'] ?? false,
            ]);

            // 2. Attach skills (roadmap_node_id)
            if (!empty($data['skills'])) {
                foreach ($data['skills'] as $nodeId) {
                    ProjectSkill::create([
                        'project_id' => $project->id,
                        'roadmap_node_id' => $nodeId,
                    ]);
                }
            }

            $userId = $project->portfolio->user_id;

            foreach ($data['skills'] ?? [] as $nodeId) {
                $node = RoadmapNode::find($nodeId);
                if (!$node) {
                    continue;
                }

                $skillId = $node->skill_id;

                $stat = UserSkillStat::firstOrNew([
                    'user_id' => $userId,
                    'skill_id' => $skillId,
                ]);

                // project lebih kuat dari progress
                $newScore = min(($stat->score ?? 0) + 20, 100);

                $stat->score = $newScore;
                $stat->level = match (true) {
                    $newScore >= 80 => 'advanced',
                    $newScore >= 50 => 'intermediate',
                    default => 'beginner'
                };
                $stat->last_updated_at = now();
                $stat->save();
            }

            // 3. Attach media
            if (!empty($data['media'])) {
                foreach ($data['media'] as $media) {
                    ProjectMedia::create([
                        'project_id' => $project->id,
                        'file_url' => $media['file_url'],
                        'file_type' => $media['file_type'] ?? 'image',
                    ]);
                }
            }

            // 4. Recalculate metrics
            $userId = $project->portfolio->user_id;
            app(PortfolioMetricService::class)->recalculate($userId);

            return [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'github_url' => $project->github_url,
                'demo_url' => $project->demo_url,
            ];
        });
    }
}
