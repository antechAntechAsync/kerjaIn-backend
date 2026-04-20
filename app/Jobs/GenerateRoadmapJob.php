<?php

namespace App\Jobs;

use App\Models\CareerRecommendation;
use App\Models\User;
use App\Contracts\AIServiceInterface;
use App\Models\Roadmap;
use App\Models\RoadmapNode;
use App\Models\RoadmapNodeResource;
use App\Models\Skill;
use App\Models\UserRoadmap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRoadmapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public function __construct(
        protected int $userId,
        protected int $careerRecommendationId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIServiceInterface $aiService): void
    {
        try {
            $user = User::findOrFail($this->userId);
            $recommendation = CareerRecommendation::findOrFail($this->careerRecommendationId);

            $roleName = $recommendation->careerRole->name ?? 'Unknown';

            // Generate roadmap data from AI
            $careers = $aiService->generateCareerRecommendation([$roleName]);

            if (empty($careers)) {
                Log::error('GenerateRoadmapJob: AI returned empty result', [
                    'user_id' => $this->userId,
                    'role' => $roleName,
                ]);

                return;
            }

            $career = $careers[0] ?? null;
            if (!$career || empty($career['roadmap'])) {
                Log::error('GenerateRoadmapJob: No roadmap in AI response');

                return;
            }

            // Create roadmap
            $roadmap = Roadmap::create([
                'career_role_id' => $recommendation->career_role_id,
                'career_role' => $roleName,
            ]);

            // Create nodes
            foreach ($career['roadmap'] as $index => $nodeData) {
                $skillName = $nodeData['skill'] ?? 'Unknown';

                // Create or find the skill
                $skill = Skill::firstOrCreate(
                    ['name' => strtolower(trim($skillName))],
                    ['name' => strtolower(trim($skillName))]
                );

                $node = RoadmapNode::create([
                    'roadmap_id' => $roadmap->id,
                    'skill_id' => $skill->id,
                    'skill_name' => $skillName,
                    'description' => $nodeData['description'] ?? '',
                    'order_index' => $index + 1,
                ]);

                // Create resources
                if (!empty($nodeData['resources'])) {
                    foreach ($nodeData['resources'] as $resource) {
                        RoadmapNodeResource::create([
                            'roadmap_node_id' => $node->id,
                            'title' => $resource['title'] ?? '',
                            'url' => $resource['url'] ?? '',
                            'type' => $resource['type'] ?? 'article',
                        ]);
                    }
                }
            }

            // Deactivate existing user roadmaps
            UserRoadmap::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create active user roadmap
            UserRoadmap::create([
                'user_id' => $user->id,
                'roadmap_id' => $roadmap->id,
                'is_active' => true,
            ]);

            Log::info('GenerateRoadmapJob: Roadmap generated successfully', [
                'user_id' => $this->userId,
                'roadmap_id' => $roadmap->id,
                'nodes_count' => count($career['roadmap']),
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateRoadmapJob failed: ' . $e->getMessage(), [
                'user_id' => $this->userId,
                'career_recommendation_id' => $this->careerRecommendationId,
            ]);

            throw $e;
        }
    }
}
