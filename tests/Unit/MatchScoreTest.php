<?php

namespace Tests\Unit;

use App\Jobs\CalculateMatchScoreJob;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Models\UserSkillScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MatchScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_score_calculation_full_match(): void
    {
        $student = User::factory()->student()->create();
        $professional = User::factory()->professional()->create();
        ProfessionalProfile::create([
            'user_id' => $professional->id,
            'company_name' => 'Test Co',
        ]);

        $job = JobListing::create([
            'user_id' => $professional->id,
            'title' => 'Backend Dev',
            'description' => 'Test job for match score calculation',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
        ]);

        // Student has all skills
        UserSkillScore::create(['user_id' => $student->id, 'skill_name' => 'php', 'score' => 90, 'source' => 'self_assessment']);
        UserSkillScore::create(['user_id' => $student->id, 'skill_name' => 'laravel', 'score' => 80, 'source' => 'self_assessment']);
        UserSkillScore::create(['user_id' => $student->id, 'skill_name' => 'mysql', 'score' => 85, 'source' => 'self_assessment']);

        $application = JobApplication::create([
            'user_id' => $student->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => 0,
        ]);

        // Run job
        $jobInstance = new CalculateMatchScoreJob($student->id, $job->id);
        $jobInstance->handle();

        $application->refresh();

        // (90 + 80 + 85) / 3 = 85
        $this->assertEquals(85, $application->match_score);
    }

    public function test_match_score_with_partial_skills(): void
    {
        $student = User::factory()->student()->create();
        $professional = User::factory()->professional()->create();
        ProfessionalProfile::create([
            'user_id' => $professional->id,
            'company_name' => 'Test Co',
        ]);

        $job = JobListing::create([
            'user_id' => $professional->id,
            'title' => 'Fullstack Dev',
            'description' => 'Test job for partial match score',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP', 'React', 'Docker'],
        ]);

        // Student only has PHP
        UserSkillScore::create(['user_id' => $student->id, 'skill_name' => 'php', 'score' => 90, 'source' => 'self_assessment']);

        $application = JobApplication::create([
            'user_id' => $student->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => 0,
        ]);

        $jobInstance = new CalculateMatchScoreJob($student->id, $job->id);
        $jobInstance->handle();

        $application->refresh();

        // (90 + 0 + 0) / 3 = 30
        $this->assertEquals(30, $application->match_score);
    }

    public function test_match_score_with_no_skills(): void
    {
        $student = User::factory()->student()->create();
        $professional = User::factory()->professional()->create();
        ProfessionalProfile::create([
            'user_id' => $professional->id,
            'company_name' => 'Test Co',
        ]);

        $job = JobListing::create([
            'user_id' => $professional->id,
            'title' => 'Senior Dev',
            'description' => 'Test job for zero match score result',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['Kubernetes', 'Terraform'],
        ]);

        $application = JobApplication::create([
            'user_id' => $student->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => 0,
        ]);

        $jobInstance = new CalculateMatchScoreJob($student->id, $job->id);
        $jobInstance->handle();

        $application->refresh();

        $this->assertEquals(0, $application->match_score);
    }
}
