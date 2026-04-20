<?php

namespace Tests\Feature\Professional;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\ProfessionalProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class JobManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;

    protected function setUp(): void
    {
        parent::setUp();

        $this->professional = User::factory()->professional()->create();
        ProfessionalProfile::create([
            'user_id' => $this->professional->id,
            'company_name' => 'PT Test Company',
        ]);
    }

    private function authHeaders(): array
    {
        $token = $this->professional->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_can_create_job(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/professional/jobs', [
                'title' => 'Backend Developer Intern',
                'description' => 'Looking for a passionate backend developer intern to join our growing team',
                'employment_type' => 'internship',
                'site_type' => 'hybrid',
                'industry' => 'Technology',
                'location' => 'Jakarta',
                'required_skills' => ['Node.js', 'REST API', 'MySQL'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Backend Developer Intern',
                    'status' => 'open',
                    'total_applicants' => 0,
                ],
            ]);

        $this->assertDatabaseHas('job_listings', [
            'user_id' => $this->professional->id,
            'title' => 'Backend Developer Intern',
        ]);
    }

    public function test_job_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/professional/jobs', [
                'title' => 'Hi', // too short
                'employment_type' => 'invalid_type',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_list_own_jobs(): void
    {
        JobListing::create([
            'user_id' => $this->professional->id,
            'title' => 'Job 1',
            'description' => 'Description for job listing number one',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/professional/jobs');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_toggle_job_status(): void
    {
        $job = JobListing::create([
            'user_id' => $this->professional->id,
            'title' => 'Test Job Listing',
            'description' => 'Description for test job listing toggle',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/professional/jobs/{$job->id}/status");

        $response->assertOk()
            ->assertJson([
                'data' => ['status' => 'closed'],
            ]);

        // Toggle back
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/professional/jobs/{$job->id}/status");

        $response->assertJson([
            'data' => ['status' => 'open'],
        ]);
    }

    public function test_cannot_access_other_professional_jobs(): void
    {
        $other = User::factory()->professional()->create();
        $job = JobListing::create([
            'user_id' => $other->id,
            'title' => 'Other Job Listing',
            'description' => 'This job belongs to another professional user',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP'],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/professional/jobs/{$job->id}");

        $response->assertStatus(404);
    }

    public function test_can_view_job_applicants(): void
    {
        $job = JobListing::create([
            'user_id' => $this->professional->id,
            'title' => 'Test Job For Applicants',
            'description' => 'Job listing to test applicant viewing feature',
            'employment_type' => 'full_time',
            'site_type' => 'wfo',
            'industry' => 'Tech',
            'status' => 'open',
            'required_skills' => ['PHP'],
        ]);

        $student = User::factory()->student()->create();
        StudentProfile::create([
            'user_id' => $student->id,
            'school_name' => 'SMK Test',
            'bio' => 'Student applying tp this job',
        ]);
        JobApplication::create([
            'user_id' => $student->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => 75,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/professional/jobs/{$job->id}/applicants");

        $response->assertOk()
            ->assertJsonPath('data.job.total_applicants', 1);
    }
}
