<?php

namespace Tests\Feature\Student;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\ProfessionalProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\UserSkillScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class JobListingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $professional;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        StudentProfile::create([
            'user_id' => $this->student->id,
            'school_name' => 'SMK Test',
            'bio' => 'Test student for job listings',
        ]);

        $this->professional = User::factory()->professional()->create();
        ProfessionalProfile::create([
            'user_id' => $this->professional->id,
            'company_name' => 'PT Test Company',
        ]);
    }

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    private function createJob(array $overrides = []): JobListing
    {
        return JobListing::create(array_merge([
            'user_id' => $this->professional->id,
            'title' => 'Backend Developer Intern',
            'description' => 'Looking for a passionate backend developer intern to join our team',
            'employment_type' => 'internship',
            'site_type' => 'hybrid',
            'industry' => 'Technology',
            'location' => 'Jakarta, Indonesia',
            'required_skills' => ['Node.js', 'REST API', 'MySQL'],
            'status' => 'open',
        ], $overrides));
    }

    public function test_student_can_list_jobs(): void
    {
        $this->createJob();
        $this->createJob(['title' => 'Frontend Developer', 'required_skills' => ['React', 'CSS']]);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson('/api/v1/student/jobs');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_jobs_by_industry(): void
    {
        $this->createJob(['industry' => 'Technology']);
        $this->createJob(['title' => 'Accountant', 'industry' => 'Finance']);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson('/api/v1/student/jobs?industry=Technology');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_jobs_by_type(): void
    {
        $this->createJob(['employment_type' => 'internship']);
        $this->createJob(['title' => 'Senior Dev', 'employment_type' => 'full_time']);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson('/api/v1/student/jobs?type=internship');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_job_detail_includes_match_score(): void
    {
        $job = $this->createJob();

        // Give student some skills
        UserSkillScore::create([
            'user_id' => $this->student->id,
            'skill_name' => 'node.js',
            'score' => 90,
            'source' => 'self_assessment',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson("/api/v1/student/jobs/{$job->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'match_score',
                    'match_details' => ['matched_skills', 'unmatched_skills'],
                ],
            ]);

        // Match score should be >0 since student has node.js skill
        $this->assertGreaterThan(0, $response->json('data.match_score'));
    }

    public function test_student_can_apply_to_job(): void
    {
        $job = $this->createJob();

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->postJson("/api/v1/student/jobs/{$job->id}/apply");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => $job->id,
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('job_applications', [
            'user_id' => $this->student->id,
            'job_listing_id' => $job->id,
        ]);
    }

    public function test_cannot_apply_twice(): void
    {
        $job = $this->createJob();

        // First apply
        $this->withHeaders($this->authHeaders($this->student))
            ->postJson("/api/v1/student/jobs/{$job->id}/apply");

        // Second apply
        $response = $this->withHeaders($this->authHeaders($this->student))
            ->postJson("/api/v1/student/jobs/{$job->id}/apply");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_apply_to_closed_job(): void
    {
        $job = $this->createJob(['status' => 'closed']);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->postJson("/api/v1/student/jobs/{$job->id}/apply");

        $response->assertStatus(403);
    }

    public function test_can_list_applied_jobs(): void
    {
        $job = $this->createJob();
        JobApplication::create([
            'user_id' => $this->student->id,
            'job_listing_id' => $job->id,
            'status' => 'pending',
            'match_score' => 50,
        ]);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson('/api/v1/student/jobs/applied');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_closed_jobs_not_shown_in_listing(): void
    {
        $this->createJob(['status' => 'open']);
        $this->createJob(['title' => 'Closed Job', 'status' => 'closed']);

        $response = $this->withHeaders($this->authHeaders($this->student))
            ->getJson('/api/v1/student/jobs');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
