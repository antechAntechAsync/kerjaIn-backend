<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\User;
use App\Models\UserSkillScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function array_map;
use function count;

class CalculateMatchScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        protected int $userId,
        protected ?int $jobListingId = null,
    ) {}

    /**
     * Calculate match scores for a user against job listings.
     *
     * If jobListingId is provided, only calculate for that job.
     * Otherwise, recalculate all applications for the user.
     */
    public function handle(): void
    {
        try {
            $user = User::findOrFail($this->userId);

            // Get user's skill scores
            $userSkills = UserSkillScore::where('user_id', $user->id)
                ->pluck('score', 'skill_name')
                ->map(fn ($score, $name) => ['name' => strtolower(trim($name)), 'score' => $score])
                ->values()
                ->keyBy('name')
                ->toArray();

            if ($this->jobListingId) {
                // Calculate for specific job
                $job = JobListing::find($this->jobListingId);
                if ($job) {
                    $score = $this->calculateScore($userSkills, $job->required_skills ?? []);
                    JobApplication::where('user_id', $user->id)
                        ->where('job_listing_id', $job->id)
                        ->update(['match_score' => $score]);
                }
            } else {
                // Recalculate all user's applications
                $applications = JobApplication::where('user_id', $user->id)
                    ->with('jobListing')
                    ->get();

                foreach ($applications as $application) {
                    if ($application->jobListing) {
                        $score = $this->calculateScore(
                            $userSkills,
                            $application->jobListing->required_skills ?? []
                        );
                        $application->update(['match_score' => $score]);
                    }
                }
            }

            Log::info('CalculateMatchScoreJob: Scores calculated', [
                'user_id' => $this->userId,
                'job_listing_id' => $this->jobListingId,
            ]);
        } catch (\Exception $e) {
            Log::error('CalculateMatchScoreJob failed: ' . $e->getMessage(), [
                'user_id' => $this->userId,
            ]);

            throw $e;
        }
    }

    /**
     * Calculate match score between user skills and job required skills.
     *
     * Algorithm:
     * - For each required skill, check if user has it
     * - If user has it: use their score (0-100)
     * - If user doesn't have it: 0
     * - Final score = avg of individual scores
     *
     * @return int Match score 0-100
     */
    protected function calculateScore(array $userSkills, array $requiredSkills): int
    {
        if (empty($requiredSkills)) {
            return 0;
        }

        $normalizedRequired = array_map(fn ($s) => strtolower(trim($s)), $requiredSkills);
        $totalScore = 0;

        foreach ($normalizedRequired as $skill) {
            if (isset($userSkills[$skill])) {
                $totalScore += $userSkills[$skill]['score'];
            }
        }

        return (int) round($totalScore / count($normalizedRequired));
    }
}
